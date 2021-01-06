var currentAlbumId = null;
var currentUserId = null;
var currentSearchFilter = null;
var currentCategoryId = null;
var hideLoader = false;
var selectedFiles = [];
var selectedFolders = [];
var lastAjaxFilter = null;
var currentSearchAdvFilters = {};

$( document ).ready(function() {
	// setup key shortcuts
	$(window).keyup(function(e) {
		// dont do anything in textareas
		target = $(e.target);
		if(target.is("textarea") == false)
		{
			// navigate files
			if (e.keyCode == 37)
			{
				$('.prev-link').click();
				return false;
			}
			else if (e.keyCode == 39)
			{
				$('.next-link').click();
				return false;
			}
		}
		
		// escape, hide any context menus
		if (e.keyCode == 27) {
			hideOpenContextMenus();
		}
		// delete key
		if (e.keyCode == 46) {
			trashFiles();
		}
	});
	
	$(window).resize(function(e) {
		// fix heights
		fixImageBrowseHeights();
	});

	// make sure the user wants to exit is they are uploading
	$(window).bind('beforeunload', function() {
		if (uploadComplete == false)
		{
			return 'You still have 1 or more uploads in progress, are you sure you want to exit?';
		}
	});
	
	$('.navbar-form-sm #searchInput').focus(function() {
        $('.navbar-form-sm').addClass('focused');
    })
    .blur(function() {
        $('.navbar-form-sm').removeClass('focused');
    });
});

window.onpopstate = function(e){
	if(e.state)
	{
		if((typeof(e.state.html) != 'undefined') && (e.state.html != null))
		{
			$('#main-ajax-container').html(e.state.html);
			document.title = e.state.pageTitle;
			setupImageBrowsePage();
		}
	}
	else
	{
		if($('.base-slide').length > 0)
		{
			$('#main-ajax-container').hide();
			$('.base-slide').first().show();
		}
	}
};

function showLayer(layerId)
{
	// clear any previous ones
	clearLayers();
	
	// load layer
	$('#'+layerId).fadeIn(300);
}

function clearLayers()
{
	$('.layer').hide();
}

function loadImages(albumId, pageStart, perPage, filterOrderBy)
{
	if(albumId == null)
	{
		albumId = currentAlbumId;
	}
	if(typeof(pageStart) == 'undefined')
	{
		pageStart = 1;
	}
	if(typeof(perPage) == 'undefined')
	{
		perPage = 0;
	}
	if(typeof(filterOrderBy) == 'undefined')
	{
		filterOrderBy = '';
	}

	if (typeof (setUploadFolderId) === 'function')
	{
		setUploadFolderId(albumId);
	}
	setLastLoadedFolderCookie(currentAlbumId);
	currentAlbumId = albumId;
	successCallback = function(data) {
		updatePageUrlBar(data.page_url, data.html, data.page_title);
		showLayer('main-ajax-container');
		scrollTop();
		setupImageBrowsePage();
	}

	loadAjaxContent('#main-ajax-container', WEB_ROOT+"/ajax/_load_album.ajax.php", {nodeId: albumId, pageStart: pageStart, perPage: perPage, filterOrderBy: filterOrderBy}, successCallback);
}

function setLastLoadedFolderCookie(folderId)
{
	$.cookie("jstree_select", "#"+folderId);
}

function setupImageBrowsePage()
{
	formatThumbLayout();
	assignLiOnClick();
        redrawInterfaceElements();
	reloadDragItems();
	highlightSelected();
	setupFileDragSelect();
	reSelectFolder();
	setupToolTips();
	// make sure treeview node is open
	$("#folderTreeview").jstree("open_node", $('.jstree #'+$('#nodeId').val()));
}

function redrawInterfaceElements()
{
    if(($('#rspShareAccessLevel').val() != 'all') && ($('#rspShareAccessLevel').val() != 'upload_download'))
    {
        $('.upload-button-wrapper').hide();
    }
    else
    {
        $('.upload-button-wrapper').show();
    }
}

function reSelectFolder()
{
	$('#folderTreeview .jstree-clicked').removeClass('jstree-clicked');
	if($('#folderTreeview #'+currentAlbumId+' > a').length > 0)
	{
		$('#folderTreeview #'+currentAlbumId+' > a').addClass('jstree-clicked');
	}
}

function loadAlbumsByUserId(userId, pageStart, perPage, filterOrderBy)
{
	if(userId == null)
	{
		userId = currentUserId;
	}
	if(typeof(pageStart) == 'undefined')
	{
		pageStart = 1;
	}
	if(typeof(perPage) == 'undefined')
	{
		perPage = 0;
	}
	if(typeof(filterOrderBy) == 'undefined')
	{
		filterOrderBy = '';
	}

	currentUserId = userId;
	successCallback = function(data) {

	}
	loadAjaxContent('#profile-albums', WEB_ROOT+"/ajax/_browse_public_albums.ajax.php", {userId: userId, pageStart: pageStart, perPage: perPage, filterOrderBy: filterOrderBy}, successCallback);
}

function loadBrowsePageAlbums(searchFilter, pageStart, perPage, filterOrderBy, advFilters)
{
	if(searchFilter == null)
	{
		searchFilter = currentSearchFilter;
	}
	if(typeof(pageStart) == 'undefined')
	{
		pageStart = 1;
	}
	if(typeof(perPage) == 'undefined')
	{
		perPage = 0;
	}
	if(typeof(filterOrderBy) == 'undefined')
	{
		filterOrderBy = '';
	}
	if(typeof(advFilters) == 'undefined')
	{
		advFilters = currentSearchAdvFilters;
	}

	currentSearchFilter = searchFilter;
	currentSearchAdvFilters = advFilters;
	successCallback = function(data) {
		setupToolTips();
	}
	loadAjaxContent('#browse-albums', WEB_ROOT+"/ajax/_browse_public_albums.ajax.php", {searchFilter: currentSearchFilter, pageStart: pageStart, perPage: perPage, filterOrderBy: filterOrderBy, advFilters: advFilters}, successCallback, false);
}

function updateBrowsePerPage(key, label, ele)
{
	$('#perPageElement').val(key);
	$('#perPageButton').html(label + ' <i class="entypo-arrow-combo"></i>');
	perPage = parseInt($('#perPageElement').val());
	if(currentUserId != null)
	{
		loadAlbumsByUserId(currentUserId, 1, perPage);
	}
	else
	{
		loadBrowsePageAlbums(currentSearchFilter, 1, perPage);
	}
}

function updateBrowseSorting(key, label, ele)
{
	$('#filterOrderBy').val(key);
	$('#filterButton').html(label + ' <i class="entypo-arrow-combo"></i>');
	filterOrderBy = $('#filterOrderBy').val();
	if(currentUserId != null)
	{
		loadAlbumsByUserId(currentUserId, 1, 0, filterOrderBy);
	}
	else
	{
		loadBrowsePageAlbums(currentSearchFilter, 1, 0, filterOrderBy);
	}
}

function loadBrowsePageCategories()
{
	successCallback = function(data) {
		setupToolTips();
	}
	loadAjaxContent('#browse-categories', WEB_ROOT+"/ajax/_browse_public_categories.ajax.php", {}, successCallback, false);
}

function loadBrowsePageCategoryImages(categoryId, pageStart, perPage, filterOrderBy)
{
	if(categoryId == null)
	{
		categoryId = currentCategoryId;
	}
	if(typeof(pageStart) == 'undefined')
	{
		pageStart = 1;
	}
	if(typeof(perPage) == 'undefined')
	{
		perPage = 0;
	}
	if(typeof(filterOrderBy) == 'undefined')
	{
		filterOrderBy = '';
	}

	currentCategoryId = categoryId;
	successCallback = function(data) {
		formatThumbLayout();
		fixImageBrowseHeights('#browse-categories ');
		assignLiOnClick();
		reloadDragItems();
	}
	loadAjaxContent('#browse-categories', WEB_ROOT+"/ajax/_load_album.ajax.php", {categoryId: currentCategoryId, pageStart: pageStart, perPage: perPage, filterOrderBy: filterOrderBy}, successCallback, false);
}

function loadBrowsePageRecentImages(searchFilter, pageStart, perPage, filterOrderBy, advFilters)
{
	if(searchFilter == null)
	{
		searchFilter = currentSearchFilter;
	}
	if(typeof(pageStart) == 'undefined')
	{
		pageStart = 1;
	}
	if(typeof(perPage) == 'undefined')
	{
		perPage = 0;
	}
	if(typeof(filterOrderBy) == 'undefined')
	{
		filterOrderBy = '';
	}
	if(typeof(advFilters) == 'undefined')
	{
		advFilters = currentSearchAdvFilters;
	}

	currentSearchFilter = searchFilter;
	currentSearchAdvFilters = advFilters;
	successCallback = function(data) {
		formatThumbLayout();
		fixImageBrowseHeights('#browse-images');
		assignLiOnClick();
		reloadDragItems();		
	}
	loadAjaxContent('#browse-images', WEB_ROOT+"/ajax/_load_album.ajax.php", {searchFilter: currentSearchFilter, searchType: 'browserecent', pageStart: pageStart, perPage: perPage, filterOrderBy: filterOrderBy, advFilters: advFilters}, successCallback, false);
}

function fixImageBrowseHeights(container)
{
	if(typeof(container) == "undefined")
	{
		container = '';
		if ($('.tab-pane.active').length)
		{
			container = '#'+$('.tab-pane.active').attr('id')+' ';
		}
	}
	$(container+'.fileIconLi').height($(container+'.fileIconLi').width());
}

// params example { name: "John", location: "Boston" }
function loadAjaxContent(container, url, params, successCallback, showLayer)
{
	if(typeof(params) == 'undefined')
	{
		params = {};
	}
	
	if(typeof(showLayer) == 'undefined')
	{
		showLayer = true;
	}
	
	// store encase we need to refresh or go back
	lastAjaxFilter = {container: container, url: url, params: params, successCallback: successCallback};
	
	$.ajax({
		method: "POST",
		url: url,
		data: params,
		dataType: "json"
	})
	.done(function(data)
	{
		// response expects:
		// data.html
		// data.javascript
		
		// populate html
		$(container).html(data.html);
		if(showLayer == true)
		{
			$(container).show();
		}
		
		// fix heights before images are loads
		fixImageBrowseHeights();
		
		// eval any javascript
		if((typeof(data.javascript) != 'undefined') && (data.javascript.length > 0))
		{
			eval(data.javascript);
		}
		
		// call any additional functions
		if(successCallback && typeof(successCallback) === "function")
		{
			successCallback(data);
		}
	});
}

function updatePageUrlBar(urlPath, historyHtml, historyPageTitle)
{
	window.history.pushState({"html":historyHtml, "pageTitle":historyPageTitle}, "", urlPath);
	document.title = historyPageTitle;
}

function scrollTop()
{
	$('html, body').animate({
		scrollTop: $(".page-body").offset().top
	}, 700);
}

function formatThumbLayout()
{
	// setup failed image handling
	$(".fileIconLi .thumbIcon img").error(function () {
		$(this).parent().parent().addClass('failedThumb');
		$(this).attr("src", SITE_IMAGE_PATH+"/trans_1x1.gif");
	});
}

function showImage(imageId)
{
	scrollTop();
	successCallback = function(data) {
		loadSimilarImages(imageId);
		updatePageUrlBar(data.page_url, data.html, data.page_title);
		setupToolTips();
		showLayer('main-ajax-container');
		setupMobileImageSwipe();
	}
	loadAjaxContent('#main-ajax-container', WEB_ROOT+"/ajax/_account_file_details.ajax.php", {u: imageId}, successCallback);
}

function setupMobileImageSwipe()
{
	// DISABLED FOR NOW AS IT CAUSES ISSUES SCROLLING THE PAGE UP AND DOWN ON MOBILE
	//$(".file-preview-wrapper .image-preview-wrapper .image").swipe({
	//	swipe:function(event, direction, distance, duration, fingerCount){
	//		if(direction == 'left')
	//		{
	//			$('.prev-link').click();
	//			event.preventDefault();
	//			return false;
	//		}
	//		else if(direction == 'right')
	//		{
	//			$('.next-link').click();
	//			event.preventDefault();
	//			return false;
	//		}
	//	},
	//	threshold: 20
	//});
}

function updatePerPage(key, label, ele)
{
	$('#perPageElement').val(key);
	$('#perPageButton').html(label + ' <i class="entypo-arrow-combo"></i>');
	perPage = parseInt($('#perPageElement').val());
	loadImages(null, 1, perPage);
}

function updateSorting(key, label, ele)
{
	$('#filterOrderBy').val(key);
	$('#filterButton').html(label + ' <i class="entypo-arrow-combo"></i>');
	filterOrderBy = $('#filterOrderBy').val();
	loadImages(null, 1, 0, filterOrderBy);
}

function updateCategoryPerPage(categoryId, key, label, ele)
{
	$('#perPageElement').val(key);
	$('#perPageButton').html(label + ' <i class="entypo-arrow-combo"></i>');
	perPage = parseInt($('#perPageElement').val());
	loadBrowsePageCategoryImages(categoryId, 1, perPage);
}

function updateRecentImagesPerPage(key, label, ele)
{
	$('#perPageElement').val(key);
	$('#perPageButton').html(label + ' <i class="entypo-arrow-combo"></i>');
	perPage = parseInt($('#perPageElement').val());
	loadBrowsePageRecentImages(currentSearchFilter, 1, perPage);
}

function updateCategorySorting(categoryId, key, label, ele)
{
	$('#filterOrderBy').val(key);
	$('#filterButton').html(label + ' <i class="entypo-arrow-combo"></i>');
	filterOrderBy = $('#filterOrderBy').val();
	loadBrowsePageCategoryImages(categoryId, 1, 0, filterOrderBy);
}

function loadSimilarImages(fileId)
{
	$.ajax({
		dataType: "json",
		url: WEB_ROOT+"/ajax/_account_file_details_similar_images.ajax.php",
		data: {u: fileId},
		success: function(data) {
			if (data.error == true)
			{
				// error
				$('.similar-images').remove();
			}
			else
			{
				// success
				$('.similar-images').html(data.html);
				formatSimilarImages();
			}
		},
		error: function(data) {
			// error
			$('.similar-images').remove();
		}
	});
}

function formatSimilarImages()
{
	$('.similar-images-list').slick({
		centerMode: false,
		slidesToScroll: 3,
		infinite: true,
		slidesToShow: 11,
		variableWidth: true,
		lazyLoad: 'ondemand'
	});
}

function showFullScreen(url, w, h)
{
	var pswpElement = document.querySelectorAll('.pswp')[0];

	// build items array
	var items = [
		{
			src: url,
			w: w,
			h: h
		}
	];
	
	// define options
	var options = {
		index: 0,
		history: false
	};
	
	// Initializes and opens PhotoSwipe
	var gallery = new PhotoSwipe( pswpElement, PhotoSwipeUI_Default, items, options);
	gallery.init();
}

function closeFullScreen()
{
	$('.fullscreenWrapper').hide();
}

function triggerFileDownload(fileId, fileHash)
{
	openUrl(WEB_ROOT+"/page/direct_download.php?fileId=" + fileId + "&fileHash="+fileHash);
}

function openUrl(url, newWindow)
{
	if(typeof(newWindow) == "undefined")
	{
		newWindow = false;
	}

	if (uploadComplete == false)
	{
		window.open(url);
	}
	else
	{
		if(newWindow == false)
		{
			window.location = url;
		}
		else
		{
			window.open(url);
		}
	}
}

function isPositiveInteger(str)
{
	var n = ~~Number(str);
	return n > 0;
}

function showFilterModal()
{
	jQuery('#filterModal').modal('show', {backdrop: 'static'}).on('shown.bs.modal', function() {
		$('#filterModal #filterFolderId').val($('#nodeId').val());
		$('#filterModal input').first().focus();
	});
}

function toggleFullScreenMode()
{
	if ((document.fullScreenElement && document.fullScreenElement !== null) ||
			(!document.mozFullScreen && !document.webkitIsFullScreen)) {
		if (document.documentElement.requestFullScreen) {
			document.documentElement.requestFullScreen();
		} else if (document.documentElement.mozRequestFullScreen) {
			document.documentElement.mozRequestFullScreen();
		} else if (document.documentElement.webkitRequestFullScreen) {
			document.documentElement.webkitRequestFullScreen(Element.ALLOW_KEYBOARD_INPUT);
		}
	} else {
		if (document.cancelFullScreen) {
			document.cancelFullScreen();
		} else if (document.mozCancelFullScreen) {
			document.mozCancelFullScreen();
		} else if (document.webkitCancelFullScreen) {
			document.webkitCancelFullScreen();
		}
	}
}

function clearSearchFilters(doFilterLocal)
{
	if (typeof(doFilterLocal) == 'undefined')
	{
		doFilterLocal = true;
	}

	$('#filterText').val('');
	$('#filterUploadedDateRange').val('');
	$('#filterUploadedDateRange').parent().find('.daterange span').html(t('file_manager_select_range'));

	if (doFilterLocal == true)
	{
		doFilter();
	}
}

function toggleViewType()
{
	if ($('.fileManager').hasClass('fileManagerList'))
	{
		$('.fileManager').removeClass('fileManagerList');
		$('.fileManager').addClass('fileManagerIcon');
		$('#viewTypeText').html('<i class="entypo-list"></i>');
		fixImageBrowseHeights();
	}
	else
	{
		$('.fileManager').addClass('fileManagerList');
		$('.fileManager').removeClass('fileManagerIcon');
		$('#viewTypeText').html('<i class="entypo-layout"></i>');
	}
	
	// store in session via ajax
	updateViewType();
}

function updateViewType()
{
	var viewType = 'fileManagerIcon';
	if ($('.fileManager').hasClass('fileManagerList'))
	{
		viewType = 'fileManagerList';
	}
	
	$.ajax({
		dataType: "json",
		url: WEB_ROOT+"/ajax/_update_view_type.ajax.php",
		data: {viewType: viewType},
		success: function(data) {
			// do nothing, assuming ok is fine in this instance
		},
		error: function(data) {
			// error
		}
	});
}

function updateSelectedItemsStatusText()
{
	count = countSelected();
	if (count == 1)
	{
		totalFilesize = getSizeSelected();
		updateStatusText(count + ' '+ t('selected', 'selected') + '&nbsp;&nbsp;<a href="#" onClick="clearSelectedItems(); return false;">('+t('selected_image_clear', 'clear')+')</a>');
	}
	else if (count > 1)
	{
		totalFilesize = getSizeSelected();
		updateStatusText(count + ' '+ t('selected', 'selected') + '&nbsp;&nbsp;<a href="#" onClick="clearSelectedItems(); return false;">('+t('selected_image_clear', 'clear')+')</a>');
	}
	else if (count == 0)
	{
		updateStatusText(null);
	}
}

function updateStatusText(text)
{
	if (text != null)
	{
		text = '<i class="entypo-bag"></i> ' + text;
	}

	$('#statusText').html(text);
}

function sharePublicAlbum(albumId)
{
	showFolderSharingForm(albumId);
}

////////////////////////////////////////////////////////////////////
// UPLOADER FUNCTIONS
////////////////////////////////////////////////////////////////////
function uploadFiles(folderId, triggerUploadBox)
{
	if(typeof(triggerUploadBox) == "undefined")
	{
		triggerUploadBox = false;
	}
	
	if (typeof(folderId) != 'undefined')
	{
		$('#upload_folder_id').val(folderId);
	}

	showUploaderPopup(triggerUploadBox);
}

function showUploaderPopup(triggerUploadBox)
{
	if(typeof(triggerUploadBox) == "undefined")
	{
		triggerUploadBox = false;
	}
	jQuery('#fileUploadWrapper').modal('show', {backdrop: 'static'}).on('shown.bs.modal').on('hidden.bs.modal', function() {
		checkShowUploadProgressWidget();
	});
	
	if(triggerUploadBox == true)
	{
		$('#add_files_btn').click();
	}
}

function assignLiOnClick()
{
	unbindLiOnClick();
	$(".fileManager .fileIconLi.owned-image a.fileDownload").click(function(e) {
		liElement = $(this).parents('.fileIconLi');
		return showFileMenu(liElement, e);
	});
	$(".fileManager .folderIconLi.owned-folder a.fileDownload").click(function(e) {
		liElement = $(this).parents('.folderIconLi');
		return showFolderMenu(liElement, e);
	});
	$(".fileManager .fileIconLi.owned-image, .fileManager .fileIconLi.not-owned-image").click(function(e) {
		e.stopPropagation();
		fileId = $(this).attr('fileId');
		selectFile(fileId);
	});
	assignLiRightClick();
}

function unbindLiOnClick()
{
	$(".fileManager .fileIconLi.owned-image").unbind('click');
	$(".fileManager .folderIconLi.owned-folder").unbind('click');
	unbindLiRightClick();
}

function unbindLiRightClick()
{
	$(".fileManager .fileIconLi.owned-image").unbind('contextmenu');
	$(".fileManager .folderIconLi.owned-folder").unbind('contextmenu');
	$(".fileManager").unbind('contextmenu');
}

function selectAllItems() {
    selectAllFolders();
    selectAllFiles();
}

function selectAllFiles()
{
    $('.fileIconLi').each(function () {
        selectFile($(this).attr('fileId'), true);
    });
}

function selectAllFolders()
{
    $('.folderIconLi').each(function () {
        selectFolder($(this).attr('folderId'), true);
    });
}

function getAllSelectedFileIds() {
    fileIds = [];
    for (i in selectedFiles) {
        fileIds.push(i.replace('k', ''));
    }

    return fileIds;
}

function getAllSelectedFolderIds() {
    folderIds = [];
    for (i in selectedFolders) {
        folderIds.push(i.replace('k', ''));
    }

    return folderIds;
}

function contextMenuIsShown()
{
	return $.vakata.context.vis;
}

function assignLiRightClick()
{
	if(loggedIn() == false)
	{
		return false;
	}

	$(".fileManager .fileIconLi.owned-image").bind('contextmenu', function(e) {
		return showFileMenu(this, e);
	});
	
	$(".fileManager .folderIconLi.owned-folder").bind('contextmenu', function(e) {
		return showFolderMenu(this, e);
	});

	$(".image-browse .toolbar-container, .image-browse .gallery-env, .image-browse .no-results-wrapper").bind('contextmenu', function(e) {
		e.stopPropagation();
		if((currentAlbumId == '-1') || ($.isNumeric(currentAlbumId)))
		{
                    currentPermission = 'view';
                    if(typeof($('#rspShareAccessLevel').val() != "undefined"))
                    {
                        currentPermission = $('#rspShareAccessLevel').val();
                    }

                    var items = {};
                    if(currentPermission != 'view')
                    {
                        items["Upload"] = {
                                        "label": t('upload_files', 'Upload Files'),
                                        "icon": "glyphicon glyphicon-cloud-upload",
                                        "separator_after": true,
                                        "action": function (obj) {
                                                uploadFiles(currentAlbumId);
                                        }
                                };
                    }

                    if(currentPermission == 'all')
                    {
                        items["Add"] = {
                                        "label": t('add_sub_folder', 'Add Sub Folder'),
                                        "icon": "glyphicon glyphicon-plus",
                                        "separator_after": true,
                                        "action": function (obj) {
                                                showAddFolderForm(currentAlbumId);
                                        }
                                };
                    }

                    items["SelectAll"] = {
                                    "label": t('account_file_details_select_all_items', 'Select All Items'),
                                    "icon": "glyphicon glyphicon-check",
                                    "action": function(obj) {
                                            selectAllItems();
                                    }
                            };

                    items["ClearAll"] = {
                                    "label": t('account_file_details_clear_selected', 'Clear Selected'),
                                    "icon": "glyphicon glyphicon-unchecked",
                                    "action": function(obj) {
                                            clearSelectedItems();
                                    }
                            };
		}
		else
		{
			var items = {
				"SelectAll": {
					"label": t('account_file_details_select_all_items', 'Select All Items'),
					"icon": "glyphicon glyphicon-check",
					"action": function(obj) {
						selectAllItems();
					}
				},
				"ClearAll": {
					"label": t('account_file_details_clear_selected', 'Clear Selected'),
					"icon": "glyphicon glyphicon-unchecked",
					"action": function(obj) {
						clearSelectedItems();
					}
				}
			};
		}
		$.vakata.context.show(items, $(this), e.pageX, e.pageY, this);
		return false;
	});
	
	// enable closing of context menus on left click
	$("body").click(function() {
		hideOpenContextMenus();
	});
}

var triggeredLoaderModal = null;
function showLoaderModal(timer)
{
    if(typeof(timer) == 'undefined')
    {
        timer = 500;
    }
    
    if(triggeredLoaderModal == null)
    {
        triggeredLoaderModal = setTimeout(showLoaderModal, timer);
        return false;
    }

    $('.loader-modal').modal('hide');
    var pleaseWaitDiv = $('<div class="modal custom-width loader-modal" id="loaderModal" data-keyboard="false"><div class="modal-dialog modal-dialog-center" style="width: 300px;"><div class="progress progress-striped active"><div class="progress-bar" style="width: 100%;"></div></div></div></div>');
    pleaseWaitDiv.modal();
    clearTimeout(triggeredLoaderModal);
    triggeredLoaderModal = null;
}

function setLoaderImage()
{
	showLoaderModal();
}

function hideLoaderModal()
{
    if(triggeredLoaderModal != null)
    {
        clearTimeout(triggeredLoaderModal);
        triggeredLoaderModal = null;
    }
    $('.loader-modal').modal('hide');
}

////////////////////////////////////////////////////////////////////
// USER AREA FUNCTIONS
////////////////////////////////////////////////////////////////////
function showEditFileForm(fileId)
{
	showLoaderModal();
	jQuery('#editFileModal .modal-content').load(WEB_ROOT+"/ajax/_account_edit_file.ajax.php", {fileId: fileId}, function() {
		hideLoaderModal();
		jQuery('#editFileModal').modal('show', {backdrop: 'static'}).on('shown.bs.modal', function() {
			toggleFilePasswordField();
			$('#editFileModal input').first().focus();
		});
	});
}

function toggleFilePasswordField()
{
	if ($('.edit-file-modal #enablePassword').is(':checked'))
	{
		$('.edit-file-modal #password').attr('READONLY', false);
	}
	else
	{
		$('.edit-file-modal #password').attr('READONLY', true);
	}
}

function toggleFolderPasswordField()
{
	if ($('.edit-folder-modal #enablePassword').is(':checked'))
	{
		$('.edit-folder-modal #password').attr('READONLY', false);
	}
	else
	{
		$('.edit-folder-modal #password').attr('READONLY', true);
	}
}

function deleteFile(fileId, callback)
{
	if (typeof(callback) == 'undefined')
	{
		callback = null;
	}
	
	clearSelectedItems();
	selectFile(fileId, true);
	selectedFiles['k' + fileId] = [fileId];
	
	return trashFiles(true, callback);
}

function trashFiles(fromFileDetails, callback)
{
	if (typeof(callback) == 'undefined')
	{
		callback = null;
	}
	
	if (typeof(fromFileDetails) == 'undefined')
	{
		fromFileDetails = false;
	}

	if (countSelected() > 0)
	{
		text = t('file_manager_are_you_sure_you_want_to_delete_x_files', 'Are you sure you want to remove the selected [[[TOTAL_FILES]]] file(s)?');
		text = text.replace('[[[TOTAL_FILES]]]', countSelected());
		if (confirm(text))
		{
			trashFilesConfirm(fromFileDetails, callback);
		}
		else
		{
			// clear selected if only 1
			if (countSelected() == 1)
			{
				clearSelectedItems();
			}
		}
	}

	return false;
}

var bulkError = '';
var bulkSuccess = '';
var totalDone = 0;
var deleteCallack = null;
function addBulkError(x)
{
	bulkError += x;
}
function getBulkError(x)
{
	return bulkError;
}
function addBulkSuccess(x)
{
	bulkSuccess += x;
}
function getBulkSuccess(x)
{
	return bulkSuccess;
}
function clearBulkResponses()
{
	bulkError = '';
	bulkSuccess = '';
}
function trashFilesConfirm(fromFileDetails, callback)
{
	if (typeof(callback) == 'undefined')
	{
		callback = null;
	}
	deleteCallack = callback;
	
	if (typeof(fromFileDetails) == 'undefined')
	{
		fromFileDetails = false;
	}

	// clear file details popup
	if (fromFileDetails == true)
	{
		//reloadPreviousAjax();
	}

	// show loader
	showLoaderModal(0);

	// prepare file ids
        fileIds = getAllSelectedFileIds();
        folderIds = getAllSelectedFolderIds();

	// get server list first
	$.ajax({
		type: "POST",
		url: CORE_AJAX_WEB_ROOT+"/_file_manage_bulk_trash.ajax.php",
		data: {fileIds: fileIds, folderIds: folderIds},
		dataType: 'json',
		success: function(json) {
			if (json.error == true)
			{
				// hide loader
				hideLoaderModal();
				$('#filePopupContentNotice').html(json.msg);
				showLightboxNotice();
			}
			else
			{
				addBulkSuccess(json.msg);
                                finishBulkProcess();
			}

		},
		error: function(XMLHttpRequest, textStatus, errorThrown) {
			$('#popupContentNotice').html('Failed connecting to server to get the list of servers, please try again later.');
			showLightboxNotice();
		}
	});
}

function finishBulkProcess()
{	
	// get final response
	bulkError = getBulkError();
	bulkSuccess = getBulkSuccess();

	// compile result
	if (bulkError.length > 0)
	{
		// hide loader
		hideLoaderModal();
		$('#filePopupContentNotice').html(bulkError + bulkSuccess);
		showLightboxNotice();
	}
	else
	{
		// hide loader
		hideLoaderModal();
	}
	clearBulkResponses();
	clearSelectedItems();
	
	if(deleteCallack != null)
	{
		deleteCallack();
		deleteCallack = null;
	}
	else
	{
		refreshFileListing();
		//refreshFolderListing();
	}

	// reload stats
	updateStatsViaAjax();
}

function deleteFiles()
{
    if (countSelected() > 0)
    {
        text = t('file_manager_are_you_sure_you_want_to_permanently_x_items', 'Are you sure you want to permanently delete the selected [[[TOTAL_ITEMS]]] item(s)?');
        text = text.replace('[[[TOTAL_ITEMS]]]', countSelected());

        if (confirm(text))
        {
            deleteFilesConfirm();
        }
        else
        {
            // clear selected if only 1
            if (countSelected() == 1)
            {
                clearSelectedItems();
            }
        }
    }

    return false;
}

function restoreItems() {
    if (countSelected() == 0) {
        alert('Please select some items to restore.');
        return false;
    }

    showLoaderModal();

    // prepare file ids
    fileIds = getAllSelectedFileIds();
    folderIds = getAllSelectedFolderIds();

    jQuery('#addEditFolderModal .modal-content').load(WEB_ROOT+"/ajax/_account_restore_from_trash.ajax.php", {fileIds: fileIds, folderIds: folderIds}, function () {
        hideLoaderModal();
        jQuery('#addEditFolderModal').modal('show', {backdrop: 'static'}).on('shown.bs.modal', function () {
            $('#addEditFolderModal input').first().focus();
        });
    });
}

function deleteFilesConfirm()
{
    // show loader
    showLoaderModal(0);

    // prepare file ids
    fileIds = getAllSelectedFileIds();
    folderIds = getAllSelectedFolderIds();

    // trigger delete
    $.ajax({
        type: "POST",
        url: CORE_AJAX_WEB_ROOT+"/_file_manage_bulk_delete.ajax.php",
        data: {fileIds: fileIds, folderIds: folderIds},
        dataType: 'json',
        success: function (json) {
            if (json.error == true)
            {
                // hide loader
                hideLoaderModal();
                $('#filePopupContentNotice').html(json.msg);
                showLightboxNotice();
            }
            else
            {
                addBulkSuccess(json.msg);
                finishBulkProcess();
            }

        },
        error: function (XMLHttpRequest, textStatus, errorThrown) {
            $('#popupContentNotice').html('Failed connecting to server to delete items, please try again later.');
            showLightboxNotice();
        }
    });
}

function countSelected()
{
    count = 0;
    for (i in selectedFiles) {
        count = count + 1;
    }

    for (i in selectedFolders) {
        count = count + 1;
    }

    return count;
}

function refreshFileListing()
{
	hideLoader = false;
	reloadPreviousAjax();
}

function reloadPreviousAjax()
{
	if(lastAjaxFilter == null)
	{
		return false;
	}

	loadAjaxContent(lastAjaxFilter.container, lastAjaxFilter.url, lastAjaxFilter.params, lastAjaxFilter.successCallback);
}

function clearSelectedItems()
{
	selectedFiles = [];
        selectedFolders = [];
	$('.selected').removeClass('selected');
	updateSelectedItemsStatusText();
	updateFileActionButtons();
}

function highlightSelected()
{
	for (i in selectedFiles)
	{
		elementId = 'fileItem' + selectedFiles[i][0];
		$('.' + elementId+'.owned-image').addClass('selected');
	}
	updateSelectedItemsStatusText();
}

function getSizeSelected()
{
	total = 0;
	for (i in selectedFiles)
	{
		itemSize = parseInt(selectedFiles[i][2]);
		total = total + itemSize;
	}

	return total;
}

function setupFileDragSelect()
{
	if (isDesktopUser() == true)
	{
		$('#fileManager .toolbar-container, #fileManager .gallery-env')
				.drag("start", function(ev, dd) {
			return $('<div class="fileManagerDraggleSelection" />')
					.css('opacity', .50)
					.appendTo(document.body);
		})
		.drag(function(ev, dd) {
			$(dd.proxy).css({
				top: Math.min(ev.pageY, dd.startY),
				left: Math.min(ev.pageX, dd.startX),
				height: Math.abs(ev.pageY - dd.startY),
				width: Math.abs(ev.pageX - dd.startX)
			});
		})
		.drag("end", function(ev, dd) {
			$(dd.proxy).remove();
		}, {distance: 10, not: $('div.image-thumb, li, span, a, img')});
		
		$('.fileIconLi, .folderIconLi').draggable({
			revert: function(event, ui) {
				return !event;
			},
			containment: 'body',
			helper: function(event) {
				if(typeof($(this).attr('fileId')) != 'undefined') {
                                    selectFile($(this).attr('fileId'), true);
                                }
                                else {
                                    selectFolder($(this).attr('folderId'), true);
                                }
				var ret = $(this).clone();
				var textStr = t('file_manager_moving', 'Moving') + ' ' + countSelected() + ' ' + t('file_manager_moving_items', 'item(s)');
				ret.find('.filename').html(textStr);
				ret.find('.back p').html(textStr);
				ret.find('.fileUploadDate').remove();
				ret.find('.filesize').remove();
				ret.find('.fileOptions').remove();
				ret.find('.downloads').remove();
				return ret;
			},
			opacity: 0.50,
			cursorAt: {left: 5, top: 5},
			distance: 10,
			start: function(event, ui)
			{
				if(typeof($(this).attr('fileId')) != 'undefined') {
                                    selectFile($(this).attr('fileId'), true);
                                }
                                else {
                                    selectFolder($(this).attr('folderId'), true);
                                }
			},
			stop: function(event, ui)
			{
				// clear selected if only 1
				if (countSelected() == 1)
				{
					if(typeof($(this).attr('fileId')) != 'undefined') {
                                            elementId = 'fileItem' + $(this).attr('fileId');
                                            $('.' + elementId).removeClass('selected');
                                            delete selectedFiles['k' + $(this).attr('fileId')];
                                        }
                                        else {
                                            elementId = 'folderItem' + $(this).attr('folderId');
                                            $('.' + elementId).removeClass('selected');
                                            delete selectedFolders['k' + $(this).attr('folderId')];
                                        }
				}
			}
		});

		setupTreeviewDropTarget();
	}
}

function setupTreeviewDropTarget()
{
	$(".jstree-no-dots li a").droppable({
		hoverClass: 'jstree-hovered',
		tolerance: "pointer",
		drop: function(event, ui) {
			folderId = $(this).parent().attr('id');
			moveFiles(folderId);
		}
	});
	
	$(".fileManager .fileListing .folderIconLi").droppable({
		hoverClass: 'jstree-hovered',
		tolerance: "pointer",
		drop: function(event, ui) {
			folderId = $(this).attr('folderid');
			moveFiles(folderId);
		}
	});
}

function setLastLoadedFolderCookie(folderId)
{
	$.cookie("jstree_select", "#"+folderId);
}

function moveFiles(newFolderId)
{
	if ((newFolderId == 'recent') || (newFolderId == 'all'))
	{
		return true;
	}

	if (newFolderId == 'trash')
	{
		trashFiles();
		return true;
	}

	moveFilesIntoFolder(newFolderId);

	return true;
}

function moveFilesIntoFolder(newFolderId)
{
	fileIds = getAllSelectedFileIds();
        folderIds = getAllSelectedFolderIds();

	$.ajax({
		dataType: "json",
		url: WEB_ROOT+"/ajax/_account_move_file_in_folder.ajax.php",
		data: {folderId: newFolderId, fileIds: fileIds, folderIds: folderIds},
		success: function(data) {
			if (data.error == true)
			{
				alert(data.msg);
			}
			else
			{
				// refresh treeview
				refreshFolderListing(false);
				refreshFileListing();

				// clear selected
				clearSelectedItems();

				// reload stats
				updateStatsViaAjax();
			}
		}
	});
}

function loggedIn()
{
	return LOGGED_IN;
}

function setUploaderFolderList(html)
{
	$('#upload_folder_id').replaceWith(html);
}

function updateAlbumCover(fileId)
{
	$.ajax({
		dataType: "json",
		url: WEB_ROOT+"/ajax/_update_album_cover.ajax.php",
		data: {fileId: fileId},
		success: function(data) {
			showSuccessNotification('Success', data.msg);
		},
		error: function(data) {
			showErrorNotification('Error', data.msg);
		}
	});
}

function hideOpenContextMenus()
{
	// hide any exiting context menus
	$.vakata.context.hide();
	$('[data-toggle="dropdown"]').parent().removeClass('open');
	clearExistingHoverFileItem();
}

function uCWords(str)
{
    return str.replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});
}