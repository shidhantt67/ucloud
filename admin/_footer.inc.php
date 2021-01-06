        <footer>
            <div class="pull-right">
                Created by <a href="<?php echo themeHelper::getCurrentProductUrl(); ?>" target="_blank"><?php echo themeHelper::getCurrentProductName(); ?></a>, a <a href="https://mfscripts.com" target="_blank">MFScripts</a> company&nbsp;&nbsp;|&nbsp;&nbsp;v<?php echo _CONFIG_SCRIPT_VERSION; ?>&nbsp;&nbsp;|&nbsp;&nbsp;<a href="https://forum.mfscripts.com" target="_blank"><?php echo t("support"); ?></a>
            </div>
            <div class="pull-left">
                <?php echo t("copyright"); ?> &copy; <?php echo date("Y"); ?> <?php echo SITE_CONFIG_SITE_NAME; ?>
            </div>
            <div class="clearfix"></div>
        </footer>

        <div id="genericModalContainer" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header"><button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">×</span></button></div>
                    <div class="modal-body"></div>
                    <div class="modal-footer"></div>
                </div>
            </div>
        </div>


        <!-- FastClick -->
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/fastclick/lib/fastclick.js"></script>
        
        <!-- NProgress -->
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/nprogress/nprogress.js"></script>
        
        <!-- iCheck -->
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/iCheck/icheck.min.js"></script>
        
        <!-- PNotify -->
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/pnotify/dist/pnotify.js"></script>
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/pnotify/dist/pnotify.buttons.js"></script>
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/pnotify/dist/pnotify.nonblock.js"></script>
        
        <!-- Datatables -->
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/datatables.net/js/jquery.dataTables.min.js"></script>
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/datatables.net-buttons-bs/js/buttons.bootstrap.min.js"></script>
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/datatables.net-buttons/js/buttons.flash.min.js"></script>
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/datatables.net-buttons/js/buttons.html5.min.js"></script>
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/datatables.net-buttons/js/buttons.print.min.js"></script>
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/datatables.net-fixedheader/js/dataTables.fixedHeader.min.js"></script>
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/datatables.net-keytable/js/dataTables.keyTable.min.js"></script>
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/datatables.net-responsive-bs/js/responsive.bootstrap.js"></script>
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/datatables.net-scroller/js/dataTables.scroller.min.js"></script>
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/jszip/dist/jszip.min.js"></script>
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/pdfmake/build/pdfmake.min.js"></script>
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/pdfmake/build/vfs_fonts.js"></script>
        
        <!-- jQuery Tags Input -->
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/jquery.tagsinput/src/jquery.tagsinput.js"></script>
        
        <!-- Typeahead/autocomplete -->
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/typeahead/bootstrap3-typeahead.min.js"></script>
        
        <!-- bootstrap-daterangepicker -->
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/moment/moment.js"></script>
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/vendors/bootstrap-daterangepicker/daterangepicker.js"></script>

        <!-- Custom Theme Scripts -->
        <script src="<?php echo ADMIN_WEB_ROOT; ?>/assets/js/custom.js"></script>

      </div>
    </div>
  </body>
</html>
