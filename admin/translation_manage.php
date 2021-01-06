<?php
// initial constants
define('ADMIN_PAGE_TITLE', 'Manage Languages');
define('ADMIN_SELECTED_PAGE', 'configuration');
define('ADMIN_SELECTED_SUB_PAGE', 'translation_manage');

// includes and security
include_once('_local_auth.inc.php');

// action rebuild request
if(isset($_REQUEST['rebuild']))
{
    $rs = translate::rebuildTranslationsFromCode();
    if($rs)
    {
        adminFunctions::setSuccess('Scan complete. Total found: ' . $rs['foundTotal'] . '. Total added: ' . $rs['addedTotal']);
    }
}

// page header
include_once('_header.inc.php');
?>

<script>
    oTable = null;
    gLanguageId = null;
    gDefaultLanguage = '';
    gEditLanguageId = null;
    $(document).ready(function () {
        // datatable
        oTable = $('#fileTable').dataTable({
            "sPaginationType": "full_numbers",
            "bServerSide": true,
            "bProcessing": true,
            "sAjaxSource": 'ajax/translation_manage.ajax.php',
            "iDisplayLength": 25,
            "aaSorting": [[1, "asc"]],
            "aoColumns": [
                {bSortable: false, sWidth: '3%', sName: 'file_icon', sClass: "center adminResponsiveHide"},
                {sName: 'language'},
                {bSortable: false, sWidth: '10%', sClass: "center adminResponsiveHide"},
                {bSortable: false, sWidth: '10%', sClass: "center adminResponsiveHide"},
                {bSortable: false, sWidth: '10%', sClass: "center adminResponsiveHide"},
                {bSortable: false, sWidth: '25%', sClass: "center dataTableFix responsiveTableColumn"}
            ],
            "fnServerData": function (sSource, aoData, fnCallback, oSettings) {
                aoData.push({"name": "filterText", "value": $('#filterText').val()});
                $.ajax({
                    "dataType": 'json',
                    "type": "GET",
                    "url": sSource,
                    "data": aoData,
                    "success": fnCallback
                });
            },
            "fnDrawCallback": function (oSettings) {
                postDatatableRender();
            },
            "oLanguage": {
                "sEmptyTable": "There are no languages in the current filters."
            },
            dom: "lBfrtip",
            buttons: [
                {
                    extend: "copy",
                    className: "btn-sm"
                },
                {
                    extend: "csv",
                    className: "btn-sm"
                },
                {
                    extend: "excel",
                    className: "btn-sm"
                },
                {
                    extend: "pdfHtml5",
                    className: "btn-sm"
                },
                {
                    extend: "print",
                    className: "btn-sm"
                }
            ]
        });

        // update custom filter
        $('.dataTables_filter').html($('#customFilter').html());
    });

    function addLanguageForm()
    {
        showBasicModal('Loading...', 'Add Language', '<button type="button" class="btn btn-primary" onClick="processAddLanguage(); return false;">Add Language</button>');
        loadAddLanguageForm();
    }

    function loadAddLanguageForm()
    {
        $.ajax({
            type: "POST",
            url: "ajax/translation_manage_add_form.ajax.php",
            data: {},
            dataType: 'json',
            success: function (json) {
                if (json.error == true)
                {
                    setBasicModalContent(json.msg);
                } else
                {
                    setBasicModalContent(json.html);
                    $('#translation_flag').selectpicker({
                        size: 8,
                        liveSearch: true
                    }).on('changed.bs.select', function (e) {
                        $('#translation_flag_hidden').val($('#translation_flag').val());
                    });
                }

            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                setBasicModalContent(XMLHttpRequest.responseText);
            }
        });
    }

    function processAddLanguage()
    {
        // get data
        translation_name = $('#translation_name').val();
        translation_flag = $('#translation_flag_hidden').val();
        direction = $('#direction').val();
        language_code = $('#language_code').val();

        $.ajax({
            type: "POST",
            url: "ajax/translation_manage_add_process.ajax.php",
            data: {translation_name: translation_name, translation_flag: translation_flag, direction: direction, language_code: language_code},
            dataType: 'json',
            success: function (json) {
                if (json.error == true)
                {
                    showError(json.msg, 'popupMessageContainer');
                } else
                {
                    showSuccess(json.msg);
                    reloadTable();
                    hideModal();
                }

            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText, 'popupMessageContainer');
            }
        });

    }

    function editLanguageForm(languageId)
    {
        gEditLanguageId = languageId;
        showBasicModal('Loading...', 'Edit Language', '<button type="button" class="btn btn-primary" onClick="processEditLanguage(); return false;">Update Language</button>');
        loadEditLanguageForm();
    }

    function loadEditLanguageForm()
    {
        $.ajax({
            type: "POST",
            url: "ajax/translation_manage_add_form.ajax.php",
            data: {languageId: gEditLanguageId},
            dataType: 'json',
            success: function (json) {
                if (json.error == true)
                {
                    setBasicModalContent(json.msg);
                } else
                {
                    setBasicModalContent(json.html);
                    $('#translation_flag').selectpicker({
                        size: 8,
                        liveSearch: true
                    }).on('changed.bs.select', function (e) {
                        $('#translation_flag_hidden').val($('#translation_flag').val());
                    });
                }

            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                setBasicModalContent(XMLHttpRequest.responseText);
            }
        });
    }

    function processEditLanguage()
    {
        // get data
        translation_name = $('#translation_name').val();
        translation_flag = $('#translation_flag_hidden').val();
        direction = $('#direction').val();
        language_code = $('#language_code').val();

        $.ajax({
            type: "POST",
            url: "ajax/translation_manage_add_process.ajax.php",
            data: {translation_name: translation_name, translation_flag: translation_flag, languageId: gEditLanguageId, direction: direction, language_code: language_code},
            dataType: 'json',
            success: function (json) {
                if (json.error == true)
                {
                    showError(json.msg, 'popupMessageContainer');
                } else
                {
                    showSuccess(json.msg);
                    reloadTable();
                    hideModal();
                }

            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText, 'popupMessageContainer');
            }
        });

    }

    function reloadTable()
    {
        oTable.fnDraw(false);
    }

    function deleteLanguage(languageId)
    {
        gLanguageId = languageId;
        showBasicModal('Are you sure you want to delete this language? Any translations will also be removed.', 'Delete Language', '<button type="button" class="btn btn-primary" onClick="removeLanguage(); return false;">Confirm Delete</button>');
    }

    function removeLanguage()
    {
        $.ajax({
            type: "POST",
            url: "ajax/translation_manage_remove.ajax.php",
            data: {languageId: gLanguageId},
            dataType: 'json',
            success: function (json) {
                if (json.error == true)
                {
                    showError(json.msg);
                } else
                {
                    showSuccess(json.msg);
                    reloadTable();
                    hideModal();
                }

            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText);
            }
        });
    }

    function setDefault(defaultLanguage)
    {
        gDefaultLanguage = defaultLanguage;
        showBasicModal('Are you sure you want to set this language as the default on the site?', 'Set Default', '<button type="button" class="btn btn-primary" onClick="setDefaultLanguage(); return false;">Confirm Default</button>');
    }

    function setDefaultLanguage()
    {
        $.ajax({
            type: "POST",
            url: "ajax/translation_manage_set_default_language.ajax.php",
            data: {defaultLanguage: gDefaultLanguage},
            dataType: 'json',
            success: function (json) {
                if (json.error == true)
                {
                    showError(json.msg);
                } else
                {
                    showSuccess(json.msg);
                    reloadTable();
                    hideModal();
                }

            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText);
            }
        });
    }

    function setAvailableState(languageId, state)
    {
        $.ajax({
            type: "POST",
            url: "ajax/translation_manage_set_available_state.ajax.php",
            data: {languageId: languageId, state: state},
            dataType: 'json',
            success: function (json) {
                if (json.error == true)
                {
                    showError(json.msg);
                } else
                {
                    showSuccess(json.msg);
                    reloadTable();
                    hideModal();
                }

            },
            error: function (XMLHttpRequest, textStatus, errorThrown) {
                showError(XMLHttpRequest.responseText);
            }
        });
    }

    function confirmRescan()
    {
        if (confirm('Are you sure you want to scan the codebase for missing translations? This will examine each file in the script for translations and automatically add the default text into the database. This process can take some time to complete.'))
        {
            window.location = 'translation_manage.php?rebuild=1';
        }

        return false;
    }
</script>

<!-- page content -->
<div class="right_col" role="main">
    <div class="">
        <div class="page-title">
            <div class="title_left">
                <h3><?php echo ADMIN_PAGE_TITLE; ?></h3>
            </div>
        </div>
        <div class="clearfix"></div>

        <?php echo adminFunctions::compileNotifications(); ?>

        <div class="row">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                    <div class="x_title">
                        <h2>Available Languages</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <table id="fileTable" class="table table-striped table-only-border dtLoading bulk_action">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t('language_name', 'Language Name')); ?></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t('default', 'Default')); ?></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t('available', 'Available')); ?></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t('direction', 'Direction')); ?></th>
                                    <th class="align-left"><?php echo UCWords(adminFunctions::t('actions', 'Actions')); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="20"><?php echo adminFunctions::t('admin_loading_data', 'Loading data...'); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="x_panel">
                    <div class="pull-left">
                        <a href="#" type="button" class="btn btn-primary" onClick="addLanguageForm(); return false;">Add Language</a>
                    </div>
                    <div class="pull-right">
                        <a href="#" type="button" class="btn btn-default" onClick="confirmRescan(); return false;">Scan For Missing Translations</a>
                        <a href="translation_manage_export.php" type="button" class="btn btn-default">Export Translations</a>
                        <a href="translation_manage_import.php" type="button" class="btn btn-default">Import Translations</a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="customFilter" id="customFilter" style="display: none;">
    <label>
        Filter Results:
        <input name="filterText" id="filterText" type="text" onKeyUp="reloadTable();
                return false;" class="form-control"/>
    </label>
</div>

<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.1/css/bootstrap-select.min.css">

<!-- Latest compiled and minified JavaScript -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.1/js/bootstrap-select.min.js"></script>

<?php
include_once('_footer.inc.php');
?>