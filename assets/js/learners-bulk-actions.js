jQuery(document).ready( function() {
    var $ = jQuery.noConflict(),
        selectedUserIds = [],
        config = sensei_learners_bulk_data,
        $hiddenSelectedUserIdsField = $('#bulk-action-user-ids'),
        $bulkLearnerActionSubmit = $('#bulk-learner-action-submit'),
        $bulkLearnerActionsForm = $('#bulk-learner-actions-form'),
        $actionSelector = $('#bulk-action-selector-top'),
        $bulkActionCourseSelect = $('#bulk-action-course-select'),
        $selectUserCheckboxes = $('.sensei_user_select_id'),
        $cbSelectAll = $('#cb-select-all-1'),
        $cbSelectAllTwo = $('#cb-select-all-2'),
        updateSelectedUserIdsFromCheckbox, logDebug;

    updateSelectedUserIdsFromCheckbox = function ($checkbox) {
        var val = parseInt($checkbox.val(), 10),
            arrayIndex = selectedUserIds.indexOf(val);
        if ($checkbox.is(':checked')) {
            if (arrayIndex < 0) {
                selectedUserIds.push(val);
            }
        } else {
            if (arrayIndex > -1) {
                selectedUserIds.splice(arrayIndex, 1);
            }
        }
        logDebug(selectedUserIds);
    };
    logDebug = function (what) {
        if (config.is_debug) {
            console.log('[Sensei v' + config.sensei_version +  ']: ' + what);
        }
    }

    logDebug('Learners Bulk Actions');

    $bulkActionCourseSelect.select2({
      placeholder: sensei_learners_bulk_data.select_course_placeholder,
      width:'300px'
    });

    $selectUserCheckboxes.on('change', function (evt) {
        var $checkbox = $(this);
        evt.preventDefault();
        evt.stopPropagation();
        updateSelectedUserIdsFromCheckbox($checkbox);
    });

    $bulkLearnerActionSubmit.on('click', function (evt) {
        evt.preventDefault();
        evt.stopPropagation();
        if (selectedUserIds.length === 0) {
            // please selectt some users
            return;
        }
        if ($actionSelector.val().trim() == '') {
            return;
        }
        if (parseInt($bulkActionCourseSelect.val(), 10) === 0) {
            // select a course
            return;
        }
        $hiddenSelectedUserIdsField.val(selectedUserIds.join(','));
        $bulkLearnerActionsForm.submit();
    });

    var hookSelectAll = function ($selectAll, $otherSelectAll) {
        $selectAll.on('click', function (evt) {
            selectedUserIds = [];
            if ($selectAll.is(':checked')) {
                $otherSelectAll.attr('checked','checked');
                $selectUserCheckboxes.attr('checked','checked');
                $selectUserCheckboxes.each(function (i, checkbox) {
                    updateSelectedUserIdsFromCheckbox($(checkbox));
                });
            } else {
                $selectUserCheckboxes.removeAttr('checked');
                $otherSelectAll.removeAttr('checked');
            }
        });
    };
    hookSelectAll($cbSelectAll, $cbSelectAllTwo);
    hookSelectAll($cbSelectAllTwo, $cbSelectAll);
});
