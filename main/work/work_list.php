<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CStudentPublication;

require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_STUDENTPUBLICATION;

api_protect_course_script(true);

require_once 'work.lib.php';
$this_section = SECTION_COURSES;

$workId = isset($_GET['id']) ? intval($_GET['id']) : null;
$courseInfo = api_get_course_info();

if (empty($workId) || empty($courseInfo)) {
    api_not_allowed(true);
}

// Student publications are saved with the iid in a LP
$origin = api_get_origin();
if ($origin == 'learnpath') {
    $em = Database::getManager();
    /** @var CStudentPublication $work */
    $work = $em->getRepository('ChamiloCourseBundle:CStudentPublication')->findOneBy(
        ['iid' => $workId, 'cId' => $courseInfo['real_id']]
    );
    if ($work) {
        $workId = $work->getId();
    }
}

protectWork($courseInfo, $workId);

$my_folder_data = get_work_data_by_id($workId);
$work_data = get_work_assignment_by_id($workId);
$tool_name = get_lang('StudentPublications');

$group_id = api_get_group_id();

$htmlHeadXtra[] = api_get_jqgrid_js();
$url_dir = api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq();

if (!empty($group_id)) {
    $group_properties = GroupManager :: get_group_properties($group_id);
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq(),
        'name' => get_lang('Groups'),
    ];
    $interbreadcrumb[] = [
        'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(),
        'name' => get_lang('GroupSpace').' '.$group_properties['name'],
    ];
}

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq(),
    'name' => get_lang('StudentPublications'),
];
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'work/work_list.php?'.api_get_cidreq().'&id='.$workId,
    'name' => $my_folder_data['title'],
];

$documentsAddedInWork = getAllDocumentsFromWorkToString($workId, $courseInfo);

$actionsLeft = '<a href="'.api_get_path(WEB_CODE_PATH).'work/work.php?'.api_get_cidreq().'">'.
    Display::return_icon('back.png', get_lang('BackToWorksList'), '', ICON_SIZE_MEDIUM).'</a>';

$actionsRight = '';
if (api_is_allowed_to_session_edit(false, true) && !empty($workId) && !api_is_invitee()) {
    $url = api_get_path(WEB_CODE_PATH).'work/upload.php?'.api_get_cidreq().'&id='.$workId;
    $actionsRight = Display::url(
        Display::return_icon(
            'upload_file.png',
            get_lang('UploadMyAssignment'),
            null,
            ICON_SIZE_MEDIUM
        ).get_lang('UploadMyAssignment'),
        $url,
        ['class' => 'btn-toolbar']
    );
}

$tpl = new Template('');

$content = Display::toolbarAction('toolbar-work', [$actionsLeft, $actionsRight]);
if (!empty($my_folder_data['title'])) {
    $content .= Display::page_subheader($my_folder_data['title']);
}

if (!empty($my_folder_data['description'])) {
    $contentWork = Security::remove_XSS($my_folder_data['description']);
    $content .= Display::panel($contentWork, get_lang('Description'));
}

$content .= workGetExtraFieldData($workId);

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;
$item_id = isset($_REQUEST['item_id']) ? intval($_REQUEST['item_id']) : null;

switch ($action) {
    case 'delete':
        $fileDeleted = deleteWorkItem($item_id, $courseInfo);

        if (!$fileDeleted) {
            Display::addFlash(Display::return_message(get_lang('YouAreNotAllowedToDeleteThisDocument')));
        } else {
            Display::addFlash(Display::return_message(get_lang('TheDocumentHasBeenDeleted')));
        }
        break;
}

$result = getWorkDateValidationStatus($work_data);
$content .= $result['message'];
$check_qualification = intval($my_folder_data['qualification']);

if (!api_is_invitee()) {
    if (!empty($work_data['enable_qualification']) && !empty($check_qualification)) {
        $type = 'simple';

        $columns = [
            get_lang('Type'),
            get_lang('Title'),
            get_lang('Qualification'),
            get_lang('Date'),
            get_lang('Status'),
            get_lang('Actions'),
        ];

        $column_model = [
            ['name' => 'type', 'index' => 'file', 'width' => '5', 'align' => 'left', 'search' => 'false', 'sortable' => 'false'],
            ['name' => 'title', 'index' => 'title', 'width' => '40', 'align' => 'left', 'search' => 'false', 'wrap_cell' => 'true'],
            ['name' => 'qualification', 'index' => 'qualification', 'width' => '30', 'align' => 'center', 'search' => 'true'],
            ['name' => 'sent_date', 'index' => 'sent_date', 'width' => '30', 'align' => 'left', 'search' => 'true', 'wrap_cell' => 'true'],
            ['name' => 'qualificator_id', 'index' => 'qualificator_id', 'width' => '20', 'align' => 'left', 'search' => 'true'],
            ['name' => 'actions', 'index' => 'actions', 'width' => '20', 'align' => 'left', 'search' => 'false', 'sortable' => 'false'],
        ];
    } else {
        $type = 'complex';

        $columns = [
            get_lang('Type'),
            get_lang('Title'),
            get_lang('Feedback'),
            get_lang('Date'),
            get_lang('Actions'),
        ];

        $column_model = [
            ['name' => 'type', 'index' => 'file', 'width' => '5', 'align' => 'left', 'search' => 'false', 'sortable' => 'false'],
            ['name' => 'title', 'index' => 'title', 'width' => '60', 'align' => 'left', 'search' => 'false', 'wrap_cell' => "true"],
            ['name' => 'qualification', 'index' => 'qualification', 'width' => '30', 'align' => 'center', 'search' => 'true'],
            ['name' => 'sent_date', 'index' => 'sent_date', 'width' => '30', 'align' => 'left', 'search' => 'true', 'wrap_cell' => 'true', 'sortable' => 'false'],
            ['name' => 'actions', 'index' => 'actions', 'width' => '20', 'align' => 'left', 'search' => 'false', 'sortable' => 'false'],
        ];
    }

    $extra_params = [
        'autowidth' => 'true',
        'height' => 'auto',
        'sortname' => 'firstname',
    ];

    $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_work_user_list&work_id='.$workId.'&type='.$type.'&'.api_get_cidreq();
    $content .= '
        <script>
            $(function() {
                '.Display::grid_js('results', $url, $columns, $column_model, $extra_params).'            
            });
        </script>
    ';

    $content .= getAllDocumentsFromWorkToString($workId, $courseInfo);

    $tableWork = Display::grid_html('results');
    $content .= Display::panel($tableWork);
}
$tpl->assign('content', $content);
$tpl->display_one_col_template();
