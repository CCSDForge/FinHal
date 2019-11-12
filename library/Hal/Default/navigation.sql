INSERT INTO `WEBSITE_NAVIGATION` (`SID`, `PAGEID`, `TYPE_PAGE`, `CONTROLLER`, `ACTION`, `LABEL`, `PARENT_PAGEID`, `PARAMS`) VALUES
(%SID%, 1, 'Hal_Website_Navigation_Page_Index', 'index', 'index', 'menu-label-1', 0, ''),
(%SID%, 2, 'Hal_Website_Navigation_Page_Submit', 'submit', 'index', 'menu-label-2', 0, ''),
(%SID%, 3, 'Hal_Website_Navigation_Page_Folder', 'section', 'browse', 'menu-label-3', 0, 'a:1:{s:9:"permalien";s:6:"browse";}'),
(%SID%, 4, 'Hal_Website_Navigation_Page_Doctype', 'browse', 'doctype', 'menu-label-4', 3, ''),
(%SID%, 5, 'Hal_Website_Navigation_Page_Author', 'browse', 'author', 'menu-label-5', 3, 'a:1:{s:5:"field";s:6:"author";}'),
(%SID%, 6, 'Hal_Website_Navigation_Page_Search', 'search', 'index', 'menu-label-6', 0, '');
