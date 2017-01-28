jQuery(document).ready( function() {
    var $ = jQuery.noConflict();
    var selectedUserIds = [];

    var config = sensei_learners_bulk_data;
    var $hidden_selected_user_ids = $('#bulk-action-user-ids');
    var $bulk_learner_action_submit = $('#bulk-learner-action-submit');
    var $bulk_learner_actions_form = $('#bulk-learner-actions-form');
    var $action_selector = $('#bulk-action-selector-top');
    var $bulk_action_course_select = $('#bulk-action-course-select');

    if (config.is_debug) {
        console.log('Sensei v' + config.sensei_version +  ': Learners Bulk Actions');
    }

    $bulk_action_course_select.select2({
      placeholder: sensei_learners_bulk_data.select_course_placeholder,
      width:'300px'
    });

    $('.sensei_user_select_id').on('change', function (evt) {
        var $checkbox = $(this),
            val = parseInt($checkbox.val(), 10),
            arrayIndex = selectedUserIds.indexOf(val);
        evt.preventDefault();
        evt.stopPropagation();
        if ($checkbox.is(':checked')) {
            if (arrayIndex < 0) {
                selectedUserIds.push(val);
            }
        } else {
            if (arrayIndex > -1) {
                selectedUserIds.splice(arrayIndex, 1);
            }
        }
    });

    $bulk_learner_action_submit.on('click', function (evt) {
        evt.preventDefault();
        evt.stopPropagation();
        if (selectedUserIds.length === 0) {
            // please selectt some users
            return;
        }
        if ($action_selector.val().trim() == '') {
            return;
        }
        if (parseInt($bulk_action_course_select.val(), 10) === 0) {
            // select a course
            return;
        }
        $hidden_selected_user_ids.val(selectedUserIds.join(','));
        console.log( 'submitting', $hidden_selected_user_ids.val());

        $bulk_learner_actions_form.submit();
    });
});
