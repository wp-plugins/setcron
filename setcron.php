<?php
/**
 * @package setcron
 */
/*
  Plugin Name: SetCron
  Plugin URI: http://www.setcron.com/
  Description: SetCron allows you to schedule cronjobs on your wordpress admin panel. This service is provided for free by SetCron.com. To get started: 1) Click the "Activate" link to the left of this description, 2) <a href="www.setcron.com/signup">Sign up for a SetCron API key</a>, and 3) Go to your SetCron configuration page, and save your API key.
  Stable tag: 1.1.4
  Version: 1.1.4
  Author: SetCron
  Author URI: http://www.setcron.com/
 */

const SetCronAPI_URL = 'https://www.setcron.com/api/server/';

function admin_add_help_tab () {
    $screen = get_current_screen();

    // Add my_help_tab if current screen is the setcron page
    $screen->add_help_tab( array(
        'id'    => 'setcron_help_tab',
        'title' => __('Help'),
        'content'   => '<p>' . __( 'For more information, check out <a href="https://www.setcron.com">https://www.setcron.com</a>' ) . '</p>',
    ) );
}


add_action('admin_menu', 'setcron_admin_settings');

add_action('admin_menu', 'setcron_admin_actions');

add_action('init', 'do_output_buffer');
function do_output_buffer() {
        ob_start();
}

function setcron_admin_settings() {
    $options_page = add_options_page('SetCron', 'SetCron', 'manage_options', 'settings', 'setcron_settings');
    add_action('load-'.$options_page, 'admin_add_help_tab');

    //call register settings function
    add_action( 'admin_init', 'register_setcron_settings' );
}

function setcron_admin_actions() {
    //create new top-level menu
    add_menu_page('', 'SetCron', 'administrator', 'setcron-tasks', 'setcron_tasks');
    add_submenu_page(
    'setcron-tasks',        // parent slug, same as above menu slug
    'Tasks',        // empty page title
    'Tasks',        // empty menu title
    'administrator',        // same capability as above
    'setcron-tasks',        // same menu slug as parent slug
    'setcron_tasks'        // same function as above
    );
    add_submenu_page('setcron-tasks','Add Task', 'Add Task', 'administrator', 'setcron-task', 'setcron_task');
}

function register_setcron_settings() {
    //register our settings
    register_setting( 'setcron-settings-group', 'setcron_apikey' );
}
?>
<?php
function setcron_settings() {
?>
<div class="wrap">
<h2>SetCron</h2>

<form method="post" action="options.php">
    <?php settings_fields( 'setcron-settings-group' ); ?>
    <?php do_settings_sections( 'setcron-settings-group' ); ?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">API Key</th>
        <td><input type="text" name="setcron_apikey" value="<?php echo get_option('setcron_apikey'); ?>" /></td>
        </tr>
       
    </table>
    
    <?php submit_button(); ?>

</form>
</div>
<?php } ?>

<?php
function setcron_notify($type,$heading,$message) {
    return '<div class="'.$type.' below-h2">
    <p><strong>'.ucwords($heading).'!</strong><br> '.$message.'</p>
</div>';
}
function setcron_table_sort_th($title,$field,$sort=null,$direction=null){
    $class = 'sortable';
    
    if(isset($sort) && $field == $sort) {
        $class = 'sorted';
        if(isset($direction) && $direction == 'asc'){
            $direction = 'asc';
            $opp_direction = 'desc';
        } else {
            $direction = 'desc';
            $opp_direction = 'asc';
        }
    } else {
        $direction = 'desc';
        $opp_direction = 'asc';
    }
    
    
    return '<th class="manage-column column-title '.$class.' '.$direction.'"><a href="'.menu_page_url('setcron-tasks',0).'&sort='.$field.'&direction='.$opp_direction.'"><span>'. $title .'</span><span class="sorting-indicator"></span></a></th>';
}
function setcron_tasks() {
        
    $notification = null;
    
    $apikey = esc_attr(get_option('setcron_apikey'));
    if(!$apikey){
        $notification = setcron_notify('error', 'error', 'Please enter your API key under Settings > SetCron .<br>You will not be able to add any tasks until you have entered a valid API key.');
    }

    if($_POST){
        $done = false;
        
        if($_POST['apply_action']){
            if($_POST['ids']){
                $data['status'] = 0;
                if($_POST['action_status']){
                    $data['status'] = $_POST['action_status'];
                }
                foreach($_POST['ids'] AS $id){
                    
                    if($data['status'] == 2){
                        $data['id'] = $id;
                        $args['headers'] = array('apikey' => $apikey, 'Accept' => 'json');
                        $args['method'] = 'DELETE';
                        $request  = wp_remote_request( SetCronAPI_URL.'task?id='.$id, $args);
                        $done = true;
                        
                    } else {
                        if($data['status'] != 1) {
                            $data['status'] = 0;
                        }
                        $data['id'] = $id;
                        $args['headers'] = array('apikey' => $apikey, 'Accept' => 'json');
                        $args['body'] = $data;
                        $args['method'] = 'PUT';
                        $request  = wp_remote_request( SetCronAPI_URL.'task?id='.$id, $args);
                        $done = true;
                    }
                    
                }
            }
        }
        
        if($done){
            wp_redirect(menu_page_url('setcron-tasks',0).'&action=tasks_updated');
        }
        
        
    }
    
    
    if(isset($_GET['action'])){
        
        if($_GET['action'] == 'task_added'){
            $notification = setcron_notify('updated', 'success', 'Task has been saved.');
        }
        if($_GET['action'] == 'tasks_updated'){
            $notification = setcron_notify('updated', 'success', 'Tasks has been updated.');
        }
        
    }
    
    $results = null;
    $filter_status = null;
    if($_POST){
        if($_POST['apply_filter']){
            $filter_status = $_POST['filter_status'];
        }
    }

    $sort = ($_GET['sort'])?$_GET['sort']:null;
    $direction = ($_GET['direction'])?$_GET['direction']:null;
    
    

    if($apikey){
        
        $args['headers'] = array('apikey' => $apikey, 'content-type' => 'application/json', 'Accept' => 'json');
        $request  = wp_remote_get( SetCronAPI_URL.'tasks?sort='.$sort.'&direction='.$direction, $args);
        $body = wp_remote_retrieve_body( $request );
        
        $response = ($body)? json_decode($body): null;
        
        if(wp_remote_retrieve_response_code($request) == 200){
            $results = $response;
        } else {
            if(isset($response->error)){
                $notification = setcron_notify('error', 'error', $response->error);
            } else {
                $notification = setcron_notify('error', 'error', 'SetCron API failed to communicate with remote server.');
            }
        }

    }
    
    $plugin_notification = wp_remote_retrieve_body(wp_remote_get('https://www.setcron.com/notification/plugin/wordpress'));
    
    ?>
    <div class="wrap">
        <h2>SetCron <a class="add-new-h2" href="<?php menu_page_url('setcron-task'); ?>">Add Task</a> <a class="add-new-h2 visit-site" target="_blank" href="https://www.setcron.com">Visit Site</a></h2>
        <div class="setcron-notification"><?php echo $plugin_notification; ?></div>
        <?php echo $notification; ?>
        <form method="post" action="<?php echo menu_page_url('setcron-tasks',0) ?>">
            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <select name="action_status">
                        <option selected="selected">Bulk Actions</option>
                        <option value="1">Activate</option>
                        <option value="0">Deactivate</option>
                        <option value="2">Delete</option>
                    </select>
                    <input type="submit" value="Apply" class="button action" name="apply_action">
                </div>
                <div class="alignleft actions">
                    <select class="postform" name="filter_status">
                        <option value="">View All</option>
                        <option value="1"<?php echo (isset($_POST['apply_filter']) && isset($_POST['filter_status']) && $_POST['filter_status'] == 1 )?' selected="selected"':''; ?>>Active</option>
                        <option value="0"<?php echo (isset($_POST['apply_filter']) && isset($_POST['filter_status']) && $_POST['filter_status'] == 0 && $_POST['filter_status'] != '' )?' selected="selected"':''; ?>>Inactive</option>
                    </select>
                    <input type="submit" value="Filter" class="button" name="apply_filter">     
                </div>
                <br class="clear">
            </div>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="cb-select-all"></th>
                            <?php echo setcron_table_sort_th(__('Task'),'alias',$_GET['sort'],$_GET['direction']); ?>
                            <?php echo setcron_table_sort_th(__('Timezone'),'timezone',$_GET['sort'],$_GET['direction']); ?>
                            <?php echo setcron_table_sort_th(__('Start'),'start',$_GET['sort'],$_GET['direction']); ?>
                            <?php echo setcron_table_sort_th(__('End'),'end',$_GET['sort'],$_GET['direction']); ?>
                            <?php echo setcron_table_sort_th(__('Active'),'status',$_GET['sort'],$_GET['direction']); ?>
                            <?php echo setcron_table_sort_th(__('ID'),'id',$_GET['sort'],$_GET['direction']); ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    
                    if ($results):
                        foreach ($results AS $row):
                            if($filter_status != ''){
                                if($row->status != $filter_status) {
                                    continue;
                                }
                            }
                            ?>
                            <tr>
                                <th><input type="checkbox" name="ids[]" value="<?php echo $row->id; ?>" class="checkbox" /></th>
                                <td><a href="<?php echo menu_page_url('setcron-task',0).'&id='.$row->id ?>"><?php echo $row->alias; ?></a><br/><?php echo $row->url; ?><br/><code><?php echo ($row->cron_minute)?$row->cron_minute:'0'; ?> <?= ($row->cron_hour)?$row->cron_hour:'0'; ?> <?= ($row->cron_day)?$row->cron_day:'0'; ?> <?= ($row->cron_month)?$row->cron_month:'0'; ?> <?= ($row->cron_week)?$row->cron_week:'0'; ?></code></td>
                                <td><?php echo $row->timezone; ?></td>
                                <td><?php echo $row->start; ?></td>
                                <td><?php echo $row->end; ?></td>
                                <td><?php echo ($row->status)?__('Yes'):__('No') ?></td>
                                <td><?php echo $row->id; ?></td>
                            </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                            <tr>
                                <td colspan="7">There are currently no tasks.</td>
                            </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>
    </div>
    <style>
        .add-new-h2.visit-site {
            background-color: #0074A2;
            color: #E0E0E0;
        }
        .setcron-notification {
            display: inline-block;
            padding: 5px 15px;
            background-color: #ffe788;
            border: 1px solid #dcc775;
        }
    </style>
    <script>
        jQuery(document).ready(function() {    
            jQuery('#cb-select-all').click(function(){
               if(jQuery(this).prop('checked')){
                   jQuery('.checkbox').prop('checked', true);
               } else {
                   jQuery('.checkbox').prop('checked', false);
               }
            });
        });
    </script>
    <?php
}
?>

<?php
function setcron_select_options($name, $options, $value, $useKey = false) {
    $select = '<select name="'.$name.'">';
    if(isset($options)){
        foreach($options AS $key => $opt){
            $selected = '';
            if($useKey){
                $selected = ($key == $value)?'selected="selected"':'';
                $select .= '<option value="'.$key.'" '.$selected.'>'.$opt.'</option>'; 
            } else {
                $selected = ($opt == $value)?'selected="selected"':'';
                $select .= '<option value="'.$opt.'" '.$selected.'>'.$opt.'</option>'; 
            }
        }
    }
    $select .= '</select>';
    return $select;
}

function setcron_task() {
    $notification = null;

    wp_enqueue_style('jquery-ui-style', plugin_dir_url(__FILE__).'assets/jquery-ui/1.10.0/themes/smoothness/jquery-ui.css');
    wp_register_style('jquery-time-picker-style' , plugin_dir_url(__FILE__).'assets/jquery-ui-timepicker-addon/css/jquery-ui-timepicker-addon.css');
    wp_enqueue_script('jquery-time-picker-script' ,  plugin_dir_url(__FILE__).'assets/jquery-ui-timepicker-addon/js/jquery-ui-timepicker-addon.min.js',  array('jquery','jquery-ui-datepicker','jquery-ui-slider'));    

    $apikey = esc_attr(get_option('setcron_apikey'));
    if(!$apikey){
        $notification = setcron_notify('error', 'error', 'Please enter your API key under Settings > SetCron .<br>You will not be able to add any tasks until you have entered a valid API key.');
    }
    $title =  __('New Task');
    $id = null;
    $item = new stdClass();
    $item->id = null;
    $item->alias = null;
    $item->url = null;
    $item->auth_user = null;
    $item->auth_password = null;
    $item->timezone = null;
    $item->start = null;
    $item->end = null;
    $item->cron_minute = null;
    $item->cron_hour = null;
    $item->cron_day = null;
    $item->cron_month = null;
    $item->cron_week = null;
    $item->status = null;

    $timezones = null;
    $task_usage = null;
    
    if(isset($_GET['id']) && $_GET['id'] != 0){
        $id = $_GET['id'];
        $title =  __('Edit Task');
    } else {
        $args['headers'] = array('apikey' => $apikey, 'content-type' => 'application/json', 'Accept' => 'json');
        $timezones_request  = wp_remote_get( SetCronAPI_URL.'timezones', $args);
        $timezones_body = wp_remote_retrieve_body( $timezones_request );
        
        $timezones_response = ($timezones_body)? json_decode($timezones_body): null;
        if(wp_remote_retrieve_response_code($timezones_request) == 200){
            $timezones = $timezones_response;
        }
    }
    if($id) {
        
        $args['headers'] = array('apikey' => $apikey, 'content-type' => 'application/json', 'Accept' => 'json');
        $request  = wp_remote_get( SetCronAPI_URL.'task?id='.$id, $args);
        $body = wp_remote_retrieve_body( $request );
        
        $response = ($body)? json_decode($body): null;
        
        if(wp_remote_retrieve_response_code($request) == 200){
            $item = $response;
        }
        
        $request2  = wp_remote_get( SetCronAPI_URL.'usage?id='.$id, $args);
        $body2 = wp_remote_retrieve_body( $request2 );
        
        $response2 = ($body2)? json_decode($body2): null;
        
        if(wp_remote_retrieve_response_code($request2) == 200){
            $task_usage = $response2;
        }

    }
    if($_POST){
        $data = $_POST;
        
        $args['headers'] = array('apikey' => $apikey, 'Accept' => 'json');
        $args['body'] = $data;
        
        if(isset($data['id'])){
            $args['method'] = 'PUT';
            $request  = wp_remote_request( SetCronAPI_URL.'task?id='.$data['id'], $args);
        } else {
            $request  = wp_remote_post( SetCronAPI_URL.'task', $args);
//            print_r($data);
//            print_r($request);
//            exit();
        }
        
        $body = wp_remote_retrieve_body( $request );
        
        $response = ($body)? json_decode($body): null;
        
        if(wp_remote_retrieve_response_code($request) == 200){
            if(isset($response->status) && $response->status == 1){
                if(!$data['id']){
                    wp_redirect(menu_page_url('setcron-tasks',0).'&action=task_added');
                    return false;
                } else {
                    $notification = setcron_notify('updated', 'success',$response->message);
                }
            } else {
                $notification = setcron_notify('error', 'error', '<p>'.implode('</p><p>',(array)$response->error).'</p>');
            }
        } else {
            $notification = setcron_notify('error','error', 'Post data failed.');
        }
        
    }
    ?>
    <div class="wrap">
        <h2><?php echo $title; ?></h2>
        <?php echo $notification; ?>
        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content" class="edit-form-section">
                    <div id="namediv" class="stuffbox">
                        <h3><label for="task"><?php echo __('Task'); ?></label></h3>
                        <div class="inside">
                        <form action="" method="post">
                            <?php if($item->id): ?>
                            <table class="form-table">
                                <tr valign="top">
                                    <td class="first"><?php echo _e('ID')?></th>
                                    <td colspan="5"><div class="regular-text ltr"><?php echo $item->id; ?><input type="hidden" name="id" value="<?php echo $item->id; ?>" /></td>
                                </tr>
                                <tr valign="top">
                                    <td class="first"><?php echo _e('Alias')?></th>
                                    <td colspan="5"><input type="text" name="alias" value="<?php echo (isset($_POST['alias'])?$_POST['alias']:$item->alias); ?>" class="regular-text ltr" /></td>
                                </tr>
                                <tr valign="top">
                                    <td class="first"><?php echo _e('Url')?></th>
                                    <td colspan="5"><div class="regular-text ltr"><?php echo $item->url; ?></td>
                                </tr>
                                <tr valign="top">
                                    <td class="first"><?php echo _e('Auth User')?></th>
                                    <td colspan="5"><input type="text" name="auth_user" value="<?php echo (isset($_POST['auth_user'])?$_POST['auth_user']:$item->auth_user); ?>" class="regular-text ltr" /></td>
                                </tr>
                                <tr valign="top">
                                    <td class="first"><?php echo _e('Auth Password')?></th>
                                    <td colspan="5"><input type="text" name="auth_password" value="<?php echo (isset($_POST['auth_password'])?$_POST['auth_password']:$item->auth_password); ?>" class="regular-text ltr" /></td>
                                </tr>
                                <tr valign="top">
                                    <td class="first"><?php echo _e('Cookie')?></th>
                                    <td colspan="5"><input type="text" name="cookie" placeholder="<?php echo __('e.g. name1=value1;name2=value2') ?>" value="<?php echo (isset($_POST['cookie'])?$_POST['cookie']:$item->cookie); ?>" class="regular-text ltr" /></td>
                                </tr>
                                <tr valign="top">
                                    <td class="first"><?php echo _e('Crontab')?></th>
                                    <td colspan="5">
                                        <span class="regular-text ltr"><?php echo $item->cron_minute; ?></span> <span class="regular-text ltr"><?php echo $item->cron_hour; ?></span> <span class="regular-text ltr"><?php echo $item->cron_day; ?></span> <span class="regular-text ltr"><?php echo $item->cron_month; ?></span> <span class="regular-text ltr"><?php echo $item->cron_week; ?></span>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <td class="first"><?php echo _e('Timezone')?></th>
                                    <td colspan="5"><div class="regular-text ltr"><?php echo $item->timezone; ?></td>
                                </tr>
                                <tr valign="top">
                                    <td class="first"><?php echo _e('Start')?></th>
                                    <td colspan="5"><input type="text" name="start" value="<?php echo (isset($_POST['start'])?$_POST['start']:$item->start); ?>" class="setcron-datetime-field regular-text ltr" /></td>
                                </tr>
                                <tr valign="top">
                                    <td class="first"><?php echo _e('End')?></th>
                                    <td colspan="5"><input type="text" name="end" value="<?php echo (isset($_POST['end'])?$_POST['end']:$item->end); ?>" class="setcron-datetime-field regular-text ltr" /></td>
                                </tr>
                                <tr valign="top">
                                    <td class="first"><?php echo _e('Active')?></th>
                                    <td colspan="5">
                                        <?php echo setcron_select_options('status',array(1=>_('Yes'),0=>_('No')), (isset($_POST['status'])?$_POST['status']:$item->status), true) ; ?>
                                    </td>
                                </tr>
                            </table>
                            <?php else: ?>
                            <table class="form-table">
                                <tr valign="top">
                                    <td class="first"><?php echo _e('Alias')?></th>
                                    <td colspan="5"><input type="text" name="alias" placeholder="<?php echo __('e.g. My Cron Script') ?>" value="<?php echo (isset($_POST['alias'])?$_POST['alias']:$item->alias); ?>" class="regular-text ltr" /></td>
                                </tr>
                                <tr valign="top">
                                    <td class="first"><?php echo _e('Url')?></th>
                                    <td colspan="5"><input type="text" name="url" placeholder="<?php echo __('e.g. http://wwww.example/script/cron.php') ?>" value="<?php echo (isset($_POST['url'])?$_POST['url']:$item->url); ?>" class="regular-text ltr" /></td>
                                </tr>
                                <tr valign="top">
                                    <td class="first"><?php echo _e('Auth User')?></th>
                                    <td colspan="5"><input type="text" name="auth_user" value="<?php echo (isset($_POST['auth_user'])?$_POST['auth_user']:$item->auth_user); ?>" class="regular-text ltr" /></td>
                                </tr>
                                <tr valign="top">
                                    <td class="first"><?php echo _e('Auth Password')?></th>
                                    <td colspan="5"><input type="text" name="auth_password" value="<?php echo (isset($_POST['auth_password'])?$_POST['auth_password']:$item->auth_password); ?>" class="regular-text ltr" /></td>
                                </tr>
                                <tr valign="top">
                                    <td class="first"><?php echo _e('Cookie')?></th>
                                    <td colspan="5"><input type="text" name="cookie" placeholder="<?php echo __('e.g. name1=value1;name2=value2') ?>" value="<?php echo (isset($_POST['cookie'])?$_POST['cookie']:$item->cookie); ?>" class="regular-text ltr" /></td>
                                </tr>
                                <tr valign="top">
                                    <td class="first"><?php echo _e('Crontab')?></th>
                                    <td><input type="text" name="cron_minute" value="<?php echo (isset($_POST['cron_minute'])?$_POST['cron_minute']:$item->cron_minute); ?>" placeholder="Minute" /></td>
                                    <td><input type="text" name="cron_hour" value="<?php echo (isset($_POST['cron_hour'])?$_POST['cron_hour']:$item->cron_hour); ?>" placeholder="Hour" /></td>
                                    <td><input type="text" name="cron_day" value="<?php echo (isset($_POST['cron_day'])?$_POST['cron_minute']:$item->cron_day); ?>" placeholder="Day of Month" /></td>
                                    <td><input type="text" name="cron_month" value="<?php echo (isset($_POST['cron_month'])?$_POST['cron_month']:$item->cron_month); ?>" placeholder="Month" /></td>
                                    <td><input type="text" name="cron_week" value="<?php echo (isset($_POST['cron_week'])?$_POST['cron_week']:$item->cron_week); ?>" placeholder="Week of Month" /></td>
                                </tr>
                                <tr valign="top">
                                    <td class="first"><?php echo _e('Timezone')?></th>
                                    <td colspan="5">
                                        <?php echo setcron_select_options('timezone',$timezones, (isset($_POST['timezone'])?$_POST['timezone']:$item->timezone)) ; ?>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <td class="first"><?php echo _e('Start')?></th>
                                    <td colspan="5"><input type="text" name="start" value="<?php echo (isset($_POST['start'])?$_POST['start']:$item->start); ?>" class="setcron-datetime-field regular-text ltr" /></td>
                                </tr>
                                <tr valign="top">
                                    <td class="first"><?php echo _e('End')?></th>
                                    <td colspan="5"><input type="text" name="end" value="<?php echo (isset($_POST['end'])?$_POST['end']:$item->end); ?>" class="setcron-datetime-field regular-text ltr" /></td>
                                </tr>
                                <tr valign="top">
                                    <td class="first"><?php echo _e('Active')?></th>
                                    <td colspan="5">
                                        <?php echo setcron_select_options('status',array(1=>_('Yes'),0=>_('No')), (isset($_POST['status'])?$_POST['status']:$item->status), true) ; ?>
                                    </td>
                                </tr>
                            </table>
                            <?php endif; ?>

                            <p class="submit">
                                <button type="submit" class="button button-primary"><?php _e('Save Changes'); ?></button>
                                <a href="<?php menu_page_url('setcron-tasks'); ?>" class="button"><?php _e('Cancel') ?></a>
                            </p>
                        </form>
                        </div>
                    </div>
                </div>
                <div id="postbox-container-1" class="postbox-container">
                    <div class="stuffbox" id="submitdiv">
                        <h3><span class="hndle">Stats</span></h3>
                        <div class="inside">
                            <table class="table table-striped table-details">
                                <tr>
                                    <th><?php echo __('Date / Time'); ?></th>
                                    <th><?php echo __('HTTP'); ?></th>
                                    <th><?php echo __('Time Lapsed'); ?></th>
                                </tr>
                            <?php if($task_usage): ?>
                            <?php foreach($task_usage AS $tu): ?>
                            <tr>
                                <td><?php echo date_format(date_create($tu->time), 'Y-m-d H:i') ?></td>
                                <td><?php echo $tu->http ?></td>
                                <td><?php echo $tu->timing ?>s</td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr><td colspan="3"><?php echo __('There is no stats for this task yet.'); ?></td></tr>
                            <?php endif;?>
                            </table>
                            <style>
                                .table-details {
                                    margin: 10px;
                                }
                                .table-details th {
                                    text-align: left;
                                }
                                .table-details td {
                                    border-bottom: 1px solid #eee;
                                }
                                .table-details th,
                                .table-details td {
                                    padding: 5px 10px;
                                }
                            </style>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        jQuery(document).ready(function() {    
            jQuery('.setcron-datetime-field').datetimepicker({dateFormat: 'yy-mm-dd',timeFormat: 'HH:mm'});
        });
    </script>
    <?php
}
?>
