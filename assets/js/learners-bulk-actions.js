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
        var validTemplate = {
            isValid: true,
            reason: ''
        };

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
            return this;
        },
        setCourseIds: function (newCourseIds) {
          courseIds = _map(newCourseIds, function (v) {
            return parseInt(v, 10);
          });
          return this;
        },
        resetSelectedUserIds: function () {
          selectedUserIds = [];
            return this;
        },
        resetAll: function () {
            this.resetSelectedUserIds();
            courseIds = [];
            bulkAction = '';
            return this;
        },
        validator: function() {
          return {
              validateBulkAction: function() {
                  if (bulkAction == '') {
                      return {
                          isValid: false,
                          reason: 'Select an action'
                      };
                  }
                  return validTemplate;
              },
              validateCourseIds: function () {
                  if (_filter(courseIds, function (v) { return !isNaN(v);}).length === 0) {
                      return {
                          isValid: false,
                          reason: 'Select a course'
                      };
                  }
                  return validTemplate;
              },
              validateSelectedUserIds: function () {
                  if (selectedUserIds.length === 0) {
                      return {
                          isValid: false,
                          reason: 'Select some learners'
                      };
                  }
                  return validTemplate;
              },
              validate: function () {
                  var validations = [
                      this.validateSelectedUserIds,
                      this.validateBulkAction,
                      this.validateCourseIds
                  ], currentValidator;

                  while (validations.length > 0) {
                      currentValidatorResult = validations.shift().call(this);
                      if (!currentValidatorResult.isValid) {
                          return currentValidatorResult;
                      }
                  }
                  return validTemplate;
              }
          }
        },
        validate: function () {
            return this.validator().validate();
        }
      }

    })();

    (function (bulkUserActions) {
        var $hiddenSelectedUserIdsField = $('#bulk-action-user-ids'),
            $hiddenSelectedCourseIdsField = $('#bulk-action-course-ids'),
            $bulkLearnerActionSubmit = $('#bulk-learner-action-submit'),
            $bulkLearnerActionsForm = $('#bulk-learner-actions-form'),
            $actionSelector = $('#bulk-action-selector-top'),
            $hiddenSenseiBulkAction = $('#sensei-bulk-action'),
            $courseSelect = $('.sensei-course-select'),
            $bulkActionCourseSelect = $('#bulk-action-course-select'),
            $selectUserCheckboxes = $('.sensei_user_select_id'),
            $cbSelectAll = $('#cb-select-all-1'),
            $learnerCourseOverviewDetail = $('.learner-course-overview-detail'),
            $learnerCourseOverviewDetailButton = $('.learner-course-overview-detail-btn'),
            $cbSelectAllTwo = $('#cb-select-all-2'),
            $modalToggle = $('#sensei-bulk-learner-actions-modal-toggle'),
            $modalContent = $('#sensei-bulk-learner-actions-modal');

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
                toggleSelectCoursesIfUsersAndBulkActionValid();
            });

        };

        var toggleSelectCoursesIfUsersAndBulkActionValid = function () {
            var validator = bulkUserActions.validator(),
                bulkActionValidationResult = validator.validateBulkAction(),
                selectedUserIdsValidationResult = validator.validateSelectedUserIds();

            if (bulkActionValidationResult.isValid && selectedUserIdsValidationResult.isValid) {
                $modalToggle.removeAttr("disabled");
            } else {
                $modalToggle.attr("disabled", true);
            }
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
            toggleSelectCoursesIfUsersAndBulkActionValid();

        });

        hookSelectAll($cbSelectAll, $cbSelectAllTwo);
        hookSelectAll($cbSelectAllTwo, $cbSelectAll);


        $modalToggle.attr("disabled", true);
        $modalToggle.on('click', function (evt) {
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

        $actionSelector.on('change', function (evt) {
            $hiddenSenseiBulkAction.val($actionSelector.val().trim());
            bulkUserActions.setAction($hiddenSenseiBulkAction.val());
            toggleSelectCoursesIfUsersAndBulkActionValid();
        });

        $bulkActionCourseSelect.on('change', function () {
            var courseIdsValid = bulkUserActions.setCourseIds(
                $bulkActionCourseSelect.val()
            ).validator().validateCourseIds().isValid;

            if (courseIdsValid) {
                $bulkLearnerActionSubmit.removeAttr("disabled");
            } else {
                $bulkLearnerActionSubmit.attr("disabled", true);
            }
        });

        $bulkLearnerActionSubmit.attr('disabled', true);

        $bulkLearnerActionSubmit.on('click', function (evt) {
            evt.preventDefault();
            evt.stopPropagation();

            bulkUserActions.setCourseIds(
              $bulkActionCourseSelect.val()
            ).setAction(
                $hiddenSenseiBulkAction.val().trim()
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

});
