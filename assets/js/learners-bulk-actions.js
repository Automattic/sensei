jQuery(document).ready(function() {
    var $ = jQuery.noConflict(),
        config = sensei_learners_bulk_data,
        _map = function (arr, fn) {
          var result = [];
          $.each(arr, function (i, v) {
            result.push(fn(v));
          });
          return result;
        },
        _filter = function (arr, pred) {
          var result = [];
          $.each(arr, function (i, v) {
            if (pred(v)) {
              result.push(v);
            }
          });
          return result;
        };

    var bulkUserActions = (function () {
      var selectedUserIds = [];
      var courseIds = [];
      var bulkAction = '';

      return {
        updateSelectedUserIdsFromCheckbox: function ($checkbox) {
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
            console.log(selectedUserIds);
            return this;
        },
        getUserIds: function () {
          return selectedUserIds;
        },
        setAction: function (ac) {
          bulkAction = ac;
        },
        setCourseIds: function (newCourseIds) {
          courseIds = _map(newCourseIds, function (v) {
            return parseInt(v, 10);
          });
          return this;
        },
        resetSelectedUserIds: function () {
          selectedUserIds = [];
          courseIds = [];
          bulkAction = '';
          return this;
        },
        validate: function () {
          if (selectedUserIds.length === 0) {
              return {
                isValid: false,
                reason: 'Select some learners'
              };
          }
          if (_filter(courseIds, function (v) { return !isNaN(v);}).length === 0) {
              return {
                isValid: false,
                reason: 'Select a course'
              };
          }
          if (bulkAction == '') {
              return {
                isValid: false,
                reason: 'Select an action'
              };
          }
          return {
            isValid: true,
            reason: ''
          };
        }
      }

    })();

    (function (bulkUserActions) {
        var $hiddenSelectedUserIdsField = $('#bulk-action-user-ids'),
            $hiddenSelectedCourseIdsField = $('#bulk-action-course-ids'),
            $bulkLearnerActionSubmit = $('#bulk-learner-action-submit'),
            $bulkLearnerActionsForm = $('#bulk-learner-actions-form'),
            $actionSelector = $('#bulk-action-selector-top'),
            $courseSelect = $('.sensei-course-select'),
            $bulkActionCourseSelect = $('#bulk-action-course-select'),
            $selectUserCheckboxes = $('.sensei_user_select_id'),
            $cbSelectAll = $('#cb-select-all-1'),
            $learnerCourseOverviewDetail = $('.learner-course-overview-detail'),
            $learnerCourseOverviewDetailButton = $('.learner-course-overview-detail-btn'),
            $cbSelectAllTwo = $('#cb-select-all-2');

        var hookSelectAll = function ($selectAll, $otherSelectAll) {
            $selectAll.on('click', function (evt) {
                bulkUserActions.resetSelectedUserIds();
                if ($selectAll.is(':checked')) {
                    $otherSelectAll.attr('checked','checked');
                    $selectUserCheckboxes.attr('checked','checked');
                    $selectUserCheckboxes.each(function (i, checkbox) {
                        bulkUserActions.updateSelectedUserIdsFromCheckbox($(checkbox));
                    });
                } else {
                    $selectUserCheckboxes.removeAttr('checked');
                    $otherSelectAll.removeAttr('checked');
                }
            });
        };


        $courseSelect.select2({
          placeholder: sensei_learners_bulk_data.select_course_placeholder,
          width:'300px'
        });

        $selectUserCheckboxes.on('change', function (evt) {
            var $checkbox = $(this);
            evt.preventDefault();
            evt.stopPropagation();
            bulkUserActions.updateSelectedUserIdsFromCheckbox($checkbox);
        });

        hookSelectAll($cbSelectAll, $cbSelectAllTwo);
        hookSelectAll($cbSelectAllTwo, $cbSelectAll);
        var $modalContent = $('#sensei-bulk-learner-actions-modal');

        var $modalToggle = $('#sensei-bulk-learner-actions-modal-toggle').on('click', function (evt) {
          $modalContent.modal({
            fadeDuration: 100,
            fadeDelay: 0.5
          });
          return false;
        });

        $learnerCourseOverviewDetailButton.on('click', function (evt) {
            evt.preventDefault();
            evt.stopPropagation();
            var $elem = $(this),
                $overviewDiv = $elem.siblings('.learner-course-overview-detail').first();

            $learnerCourseOverviewDetail.filter(':visible').slideUp( "slow" );
            if ( $overviewDiv.is( ":hidden" ) ) {
                $overviewDiv.slideDown("slow");
            } else {
                $overviewDiv.slideUp("slow");

            }
        });

        $bulkLearnerActionSubmit.on('click', function (evt) {
            evt.preventDefault();
            evt.stopPropagation();

            bulkUserActions.setCourseIds(
              $bulkActionCourseSelect.val()
            ).setAction(
              $actionSelector.val().trim()
            );

            var validationResult = bulkUserActions.validate();
            if (!validationResult.isValid) {
              console.log(validationResult);
              return;
            }

            $hiddenSelectedUserIdsField.val(bulkUserActions.getUserIds().join(','));
            $hiddenSelectedCourseIdsField.val($bulkActionCourseSelect.val().join(','));
            $bulkLearnerActionsForm.submit();
        });

    })(bulkUserActions);



    // var $trg = $('#bulk-learner-actions-trigger-btn');
    // var allFields = $([]);

    // };
    // // var tpl = _.template($('#sensei-bulk-actions-modal-tpl').html());
    // // console.log(tpl());
    //
    //
    //

    //

    //

});
