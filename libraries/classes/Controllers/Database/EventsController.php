<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Common;
use PhpMyAdmin\Database\Events;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;
use function strlen;

final class EventsController extends AbstractController
{
    /** @var Events */
    private $events;

    /** @var DatabaseInterface */
    private $dbi;

    /**
     * @param Response          $response
     * @param string            $db       Database name.
     * @param DatabaseInterface $dbi
     */
    public function __construct($response, Template $template, $db, Events $events, $dbi)
    {
        parent::__construct($response, $template, $db);
        $this->events = $events;
        $this->dbi = $dbi;
    }

    public function index(): void
    {
        global $db, $tables, $num_tables, $total_num_tables, $sub_part, $errors, $text_dir, $PMA_Theme;
        global $is_show_stats, $db_is_system_schema, $tooltip_truename, $tooltip_aliasname, $pos;

        if (! $this->response->isAjax()) {
            Common::database();

            [
                $tables,
                $num_tables,
                $total_num_tables,
                $sub_part,
                $is_show_stats,
                $db_is_system_schema,
                $tooltip_truename,
                $tooltip_aliasname,
                $pos,
            ] = Util::getDbInfo($db, $sub_part ?? '');
        } elseif (strlen($db) > 0) {
            $this->dbi->selectDb($db);
        }

        /**
         * Keep a list of errors that occurred while
         * processing an 'Add' or 'Edit' operation.
         */
        $errors = [];

        $this->events->handleEditor();
        $this->events->export();

        $items = $this->dbi->getEvents($db);

        $this->render('database/events/index', [
            'db' => $db,
            'items' => $items,
            'select_all_arrow_src' => $PMA_Theme->getImgPath() . 'arrow_' . $text_dir . '.png',
            'has_privilege' => Util::currentUserHasPrivilege('EVENT', $db),
            'toggle_button' => $this->events->getFooterToggleButton(),
            'is_ajax' => $this->response->isAjax() && empty($_REQUEST['ajax_page_request']),
        ]);
    }
}
