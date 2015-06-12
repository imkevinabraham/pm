var DUET = DUET || {};


DUET.routes = {

    //todo: add back default route, but dashboard should only run iff language and templates have been loaded - wait for them , '*path':'dashboard'
    routes:{
        ':referenceObjectPlural/:referenceId/discussion':'discussion',
        'projects/:id/:entityType/:entityId/discussion':'discussion',
        'projects/:id/:entityType':'projectEntityList',
        'projects/:id/:entityType/:entityId':'projectEntity',
        'projects/:id/invoices/:invoiceId/:invoiceAction':'invoiceScreens',
        'projects/:id':'projectEntityList',
        'projects':'projectEntityList',
        'templates/:id/:entityType':'templateEntityList',
        'templates/:id/:entityType/:entityId':'templateEntity',
        'templates/:id':'templateEntityList',
        'templates':'templateEntityList',
        'tasks/:id':'task',
        'tasks':'task',
        'clients/:id':'client',
        'clients':'client',
        'users/:id':'user',
        'users':'user',
        'invoices':'invoice',
        'invoices/:id':'invoice',
        'files':'file',
        'files/:id':'file',
        'login':'login',
        'logout':'logout',
        'dashboard':'dashboard',
        'profile':'myProfile',
        'search/:query':'search',
        'forgot_password':'forgotPassword',
        'admin':'admin',
        'admin/*path':'admin',
        'reporting':'reporting',
        'discussion/:referenceObject/:referenceId':'discussion',
        '':'dashboard'
    },
    projectEntityList:function (projectId, entityType, projectType) {
        var args, panelLoaded, params;

        projectType = projectType || 'project';


        if (!projectId)
            panelLoaded = DUET.routeHelpers.initPrimaryPanel(projectType, projectId);
        else {

            DUET.panelTwo.loading();

            args = arguments;

            params = DUET.routeHelpers.initSecondaryPanel(args);

            function collectionHandler() {
                var collection, view, pluralModelName = params.activeModelSingular + 's';

                //because the word 'peoples' makes no sense
                if (pluralModelName == 'peoples')
                    pluralModelName = 'people';

                collection = new DUET.Collection({
                    model:params.activeModelSingular,
                    url:'projects/' + projectId + '/' + pluralModelName
                });

                //todo: maybe some kind of loading text while the collection is loading for slow connections
                //TODO:clicking on any of these list items reloads the entire page. not cool
                collection.on('loaded', function () {
                    view = new DUET[DUET.utils.ucFirst(params.activeModelSingular) + 'ListView'](collection, params.project);
                    DUET.routeHelpers.panelTwoHandler(params, view);
                });

                collection.load();
            }

            function modelHandler() {
                var modelName, model, viewNamePrefix, view;

                modelName = params.activeModelName;
                if (DUET[modelName]) {
                    model = new DUET[modelName];
                }
                else {
                    modelName = ut.ucFirst(projectType) + params.activeModelName;
                    model = new DUET[modelName];
                }

                model.on('loaded', function () {
                    viewNamePrefix = DUET.utils.ucFirst(params.activeModelName);

                    if (DUET[viewNamePrefix + 'View'])
                        view = new DUET[viewNamePrefix + 'View'](model);
                    else view = new DUET['Project' + viewNamePrefix + 'View'](model);

                    DUET.routeHelpers.panelTwoHandler(params, view);
                });

                model.load(projectId);
            }


            if (params.project) {

                $.when(params.project.isLoaded).done(function () {
                    DUET.panelTwo.setTitle(params.project.name);
                    DUET.panelTwo.setModel(params.project);
                });

            }

            if (params.activeModel != 'calendar' && params.activeModel != 'details' && params.activeModel != 'notes' && params.activeModel != 'people') {
                collectionHandler();
            }
            else {
                modelHandler();
            }
        }
    },
    projectEntity:function (projectId, entityType, entityId, projectType) {
        var args, panelLoaded, params;


        projectType = projectType || 'project';

        DUET.panelTwo.loading();

        args = arguments;

        params = DUET.routeHelpers.initSecondaryPanel(args);
        DUET.panelTwo.setModel(params.project);

        $.when(params.project.isLoaded).done(function () {


            var activeModelUppercase = DUET.utils.ucFirst(params.activeModelSingular);
            var model = new DUET[activeModelUppercase];

            model.on('loaded', function () {
                // DUET.context(params.activeModelSingular, model.id);
                var view = new DUET[activeModelUppercase + 'View'](model);
                DUET.panelTwo.setInnerContent(view);
                DUET.panelTwo.buildProjectItemCategories('project-' + params.activeModel);
            });

            model.load(params.activeModelId);
        });

    },
    templateEntityList:function (projectId, entityType) {
        DUET.routes.projectEntityList(projectId, entityType, 'template');
    },
    templateEntity:function (projectId, entityType, entityId) {
        DUET.routes.projectEntity(projectId, entityType, entityId, 'template');
    },
    invoiceScreens:function (projectId, invoiceId, invoiceAction) {
        var invoice, view, args, params,
            panelLoaded = DUET.routeHelpers.initPrimaryPanel('project', projectId); //todo:the primary panel probably isn't relevant here anymore.

        DUET.panelTwo.loading();

        args = arguments;

        $.when(panelLoaded).done(function () {
            invoice = new DUET.Invoice();
            params = DUET.routeHelpers.initSecondaryPanel(args);

            DUET.panelTwo.setModel(params.project);

            //prevent a user from opening up the build or import views
            //if opened, they still wouldn't be able to modify the invoice because it's restricted on the server side
            if (!DUET.userIsAdmin() && invoiceAction != 'preview')
                return false;

            invoice.on('loaded', function () {
                if (invoiceAction == 'build')
                    view = new DUET.InvoiceEditorView(invoice);
                else if (invoiceAction == 'import')
                    view = new DUET.InvoiceImportView(invoice);
                else if (invoiceAction == 'preview')
                    view = new DUET.InvoicePreviewView(invoice);

                DUET.routeHelpers.panelTwoHandler(params, view);
            });

            invoice.load(invoiceId);
        });
    },
    task:function (id) {
        DUET.baseModelRoute('task', id);
    },
    client:function (id) {
        DUET.baseModelRoute('client', id);
    },
    user:function (id) {
        DUET.baseModelRoute('user', id);
    },
    invoice:function (id) {
        DUET.baseModelRoute('invoice', id);
    },
    file:function (id) {
        DUET.baseModelRoute('file', id);
    },
    dashboard:function () {
        var dashboardView,
            dashboard = new DUET.Dashboard();

        DUET.panelTwo.loading();

        //todo:this route is getting called before the initialization has completed, causing this if statement to be required. This shouldn't be necessary.
        if (DUET.initComplete == true) {
            DUET.panelTwo.setTitle(ut.lang('sidebar.dashboard'));
            DUET.panelTwo.setModel(dashboard);

            DUET.slideOutPanel.hide();
        }

        dashboard.on('loaded', function () {
            dashboardView = new DUET.DashboardView(dashboard);
            DUET.panelTwo.setContent(dashboardView);
        });

        dashboard.load(1);
    },
    login:function () {
        DUET.stop();
    },
    logout:function () {
        new DUET.Request({
            url:'app/logout',
            success:function () {
                window.location = '#login';
            }
        });
    },
    myProfile:function () {
        this.user(DUET.my.id);
        DUET.slideOutPanel.hide();

    },
    search:function (query) {

        var searchModel = new DUET.Search();


        //DUET.context('search', 1);
        DUET.panelTwo.loading();
        DUET.panelTwo.setTitle('Search results for \'' + query + '\''); //todo:lang file
        DUET.panelTwo.setModel(searchModel);
        DUET.panelTwo.removeTitleWidget();
        DUET.slideOutPanel.hide();

        searchModel.on('loaded', function () {
            var searchResultsView = new DUET.SearchResultsView(searchModel);

            DUET.panelTwo.setContent(searchResultsView);
        });

        searchModel.load(query);
    },
    forgotPassword:function () {
        var forgotPasswordView = new DUET.ForgotPasswordView();
        forgotPasswordView.addTo({$anchor:$('body')});
    },
    admin:function (tab) {
        if (!DUET.userIsAdmin())
            return false;

        DUET.panelTwo.loading();

        var settings = new DUET.Setting();

        DUET.panelTwo.setTitle(ut.lang('adminSettings.title'));
        DUET.panelTwo.setModel();
        DUET.slideOutPanel.hide();

        settings.on('loaded', function () {

            var adminView = new DUET.AdminView(settings, tab);
            DUET.panelTwo.setContent(adminView);
        });

        settings.load(1);

    },
    reporting:function () {
        var reports = new DUET.Reports();

        DUET.panelTwo.loading();

        //DUET.context('reporting', 1);
        DUET.panelTwo.setTitle(ut.lang('reporting.title'));
        DUET.panelTwo.setModel();

        DUET.slideOutPanel.hide();


        reports.on('loaded', function () {
            var reportingView = new DUET.ReportingView(reports);
            DUET.panelTwo.setContent(reportingView);
        });

        reports.load(1);
    },
    discussion:function (referenceObjectPlural, referenceId) {
        var params = arguments, referenceObject, discussion;

        DUET.slideOutPanel.hide();
        DUET.panelTwo.loading();

        if (params.length == 2) {
            referenceObjectPlural = params[0];
            referenceId = params[1];
        }
        else {
            referenceObjectPlural = params[1];
            referenceId = params[2];

        }

        referenceObject = referenceObjectPlural.slice(0, -1);

        discussion = new DUET.Discussion({
            referenceObject:referenceObject,
            referenceId:referenceId
        });

        DUET.context(referenceObject, referenceId);

        discussion.load(1);

        discussion.on('loaded', function () {
            var project = new DUET.Project();

            if(discussion.entity.type == 'project')
                project.load(discussion.entity);
            else project.load({id:discussion.entity.projectId});

            DUET.panelTwo.setModel(project);
            DUET.panelTwo.setTitle(ut.lang('messagesPanel.discussionFor', discussion) + DUET.getModelTitle(discussion.entity));
            var discussionView = new DUET.DiscussionView(discussion);
            DUET.panelTwo.setInnerContent(discussionView);


            DUET.panelTwo.buildProjectItemCategories('project-discussion');
        });
    }

};


//common functions used throughout the routes
DUET.routeHelpers = {
    initPrimaryPanel:function (projectType) {
        DUET.slideOutPanel.setList(projectType);
    },
    initSecondaryPanel:function (params) {
        var collection, view, activeModel, activeModelSingular, activeModelName, project, params, projectId, activeModelId;

        //secondary panel
        projectId = params[0];
        activeModel = params[1] || DUET.options.defaultProjectTab;
        activeModelName = DUET.utils.ucFirst(activeModel);
        activeModelId = params[2];
        activeModelSingular = DUET.utils.trim(activeModel, 's');

        var project = params[2] !== 'template' ? new DUET.Project() : new DUET.Template();
        project.load(projectId);

        DUET.slideOutPanel.hide();

        return{
            activeModel:activeModel,
            activeModelName:activeModelName,
            activeModelId:activeModelId,
            activeModelSingular:activeModelSingular,
            project:project
        };
    },
    collectionHandler:function () {
    },
    panelTwoHandler:function (params, view) {
        var context;


        DUET.panelTwo.setInnerContent(view); //TODO: Think about having a DUET.setContent('panelTwo', view.get()), basically an app level set content function?
        DUET.panelTwo.buildProjectItemCategories('project-' + params.activeModel);
        context = DUET.context();

        if (context && (context.object == 'project')) {
            var progressWidget = new DUET.ProjectProgressTitleWidgetView(params.project);
            DUET.panelTwo.setTitleWidget(progressWidget);
        }
    }
};

DUET.getModelTitle = function (model) {
    var type = model.type,
        title = '';

    switch (type) {
        case 'project':
        case 'client':
        case 'file':
            title = model.name;
            break;
        case 'task':
            title = 'Task: ' + model.task.substr(0, 10) + '...';
            break;
        case 'invoice':
            title = 'Invoice ' + model.number;
            break;
        case 'user':
            title = model.firstName + ' ' + model.lastName;
            break;
        case 'dashboard':
            title = 'Dashboard';
            break;
    }

    return title;

};

DUET.baseModelRoute = function (modelType, id) {
    var model, view, modelData, modelTypeU = DUET.utils.ucFirst(modelType);


    if (!id)
        DUET.slideOutPanel.setList(modelType);
    else {

        DUET.panelTwo.loading();

        DUET.slideOutPanel.hide();

        modelData = id;

        //secondary panel
        model = new DUET[modelTypeU];

        model.on('loaded', function () {
            //  DUET.context(modelType, model.id);
            if (DUET[modelTypeU + 'DetailsView'])
                view = new DUET[modelTypeU + 'DetailsView'](model);
            else view = new DUET[modelTypeU + 'View'](model);

            DUET.panelTwo.removeTitleWidget();
            DUET.panelTwo.setTitle(DUET.getModelTitle(model));
            DUET.panelTwo.setContent(view);
            DUET.panelTwo.setModel(model);
        });

        model.load(modelData);
    }

};
