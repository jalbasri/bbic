/**
 * Nooku Framework - http://nooku.org/framework
 *
 * @codekit-prepend "spin.min.js", "files.utilities.js", "files.state.js", "files.template.js", "files.grid.js", files.tree.js", "files.row.js", "files.paginator.js", "files.pathway.js"
 *
 * @copyright	Copyright (C) 2011 - 2014 Johan Janssens and Timble CVBA. (http://www.timble.net)
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html>
 * @link		http://github.com/nooku/nooku-files for the canonical source repository
 */

if(!Files) var Files = {};

Files.blank_image = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAMAAAAoyzS7AAAABGdBTUEAALGPC/xhBQAAAAd0SU1FB9MICA0xMTLhM9QAAAADUExURf///6fEG8gAAAABdFJOUwBA5thmAAAACXBIWXMAAAsSAAALEgHS3X78AAAACklEQVQIHWNgAAAAAgABz8g15QAAAABJRU5ErkJggg==';

Files.App = new Class({
    Implements: [Events, Options],

    _tmpl_cache: {},
    active: null,
    title: '',
    cookie: null,
    options: {
        root_text: 'Root folder',
        cookie: {
            path: '/'
        },
        persistent: true,
        thumbnails: true,
        types: null,
        container: null,
        active: null,
        pathway: {
            element: 'files-pathway'
        },
        state: {
            defaults: {}
        },
        tree: {
            enabled: true,
            element: '#files-tree'
        },
        grid: {
            element: 'files-grid',
            batch_delete: '#toolbar-delete a',
            icon_size: 150
        },
        paginator: {
            element: 'files-paginator'
        },
        folder_dialog: {
            view: '#files-new-folder-modal',
            input: '#files-new-folder-input',
            open_button: '#toolbar-newfolder a',
            create_button: '#files-new-folder-create',
            //Fires when the form for creating a new folder is submitted
            onSubmit: function(){},
            //Fires when the json request for creating a folder is complete
            onCreate: function(folder_dialog){
                folder_dialog.input.set('value', '');
                folder_dialog.create_button.removeClass('valid').setProperty('disabled', 'disabled');
            },
            onOpen: function(folder_dialog){
                kQuery.magnificPopup.open({
                    items: {
                        src: kQuery(folder_dialog.view),
                        type: 'inline'
                    },
                    callbacks: {
                        open: function(){
                            setTimeout(function(){
                                folder_dialog.input.focus();
                            }, 100);
                        }
                    }
                });
            },
            onClose: function(){
                kQuery.magnificPopup.close();
            },
            onInit: function(folder_dialog){
                var input    = kQuery(folder_dialog.input),
                    trigger  = kQuery(folder_dialog.create_button),
                    validate = function(){
                        if (kQuery.trim(kQuery(this).val())) {
                            trigger.addClass('valid').prop('disabled', false);
                        } else {
                            trigger.removeClass('valid').prop('disabled', true);
                        }
                    };

                input.on('change', validate);

                if(window.addEventListener) {
                    input.on('input', validate);
                } else {
                    input.on('keyup', validate);
                }
            }
        },
        uploader_dialog: {
            view: '#files-upload',
            button: '#toolbar-upload a'
        },
        history: {
            enabled: true
        },
        router: {
            defaults: {
                option: 'com_files',
                view: 'files',
                format: 'json'
            }
        },
        initial_response: null,
        refresh_button: '#toolbar-refresh',

        onAfterSetGrid: function(){
            window.addEvent('resize', function(){
                this.setDimensions(true);
            }.bind(this));
            this.grid.addEvent('onAfterRenew', function(){
                this.setDimensions(true);
            }.bind(this));
            this.addEvent('onUploadFile', function(){
                this.setDimensions(true);
            }.bind(this));
        },
        onAfterNavigate: function(path) {
            if (path !== undefined) {
                this.setTitle(this.folder.name || this.container.title);
                kQuery('#upload-files-to, .upload-files-to').text(path ? path : this.container.title);
            }
        },
        onBeforeSetContainer: function(response) {
            if (typeof response.container !== 'undefined') {
                response.container.title = this.options.root_text;
            }
        }
    },

    initialize: function(options) {
        this.setOptions(options);

        if (this.options.persistent && this.options.container) {
            var container = typeof this.options.container === 'string' ? this.options.container : this.options.container.slug;
            this.cookie = 'com.files.container.'+container;
        }

        if(this.options.pathway) {
            this.setPathway();
        }
        this.setState();
        this.setHistory();
        this.setGrid();
        this.setPaginator();

        var url = this.getUrl();
        if (url.getData('container') && !this.options.container) {
            this.options.container = url.getData('container');
        }

        if (url.getData('folder')) {
            this.options.active = url.getData('folder');
        }

        if (this.options.thumbnails) {
            this.addEvent('afterSelect', function(resp) {
                this.setThumbnails();
            });
        }

        if(this.options.uploader_dialog) {
            this.setUploaderDialog();
        }

        if (this.options.container) {
            this.setContainer(this.options.container);
        }

        if (this.options.refresh_button) {
            var refresh = document.getElement(this.options.refresh_button),
                self    = this;

            if (refresh) {
                refresh.addEvent('click', function(e) {
                    e.stop();
                    self.navigate(undefined, 'stateless', true);
                    self.setTree();
                });
            }
        }
    },
    setState: function() {
        this.fireEvent('beforeSetState');

        if (this.cookie) {
            var state = Cookie.read(this.cookie+'.state'),
                obj   = JSON.decode(state, true);

            if (obj) {
                if (typeof this.getUrl().getData('folder') === 'undefined') {
                    this.options.active = obj.folder;
                }

                delete obj.folder;

                this.options.state.defaults = Object.merge({}, this.options.state.defaults, obj);

            }

        }

        var opts = this.options.state;
        this.state = new Files.State(opts);

        this.fireEvent('afterSetState');
    },
    setHistory: function() {
        this.fireEvent('beforeSetHistory');

        if (this.options.history.enabled) {
            var that = this;
            this.history = History;
            window.addEvent('popstate', function(e) {
                if (e) { e.stop(); }

                var state = History.getState(),
                    old_state = that.state.getData(),
                    new_state = state.data,
                    state_changed = false;

                Object.each(old_state, function(value, key) {
                    if (state_changed === true) {
                        return;
                    }
                    if (new_state && new_state[key] && value !== new_state[key]) {
                        state_changed = true;
                    }
                });

                if (that.container && (state_changed || that.active !== state.data.folder)) {
                    var set_state = Object.append({}, state.data);
                    ['option', 'view', 'layout', 'folder', 'container'].each(function(key) {
                        delete set_state[key];
                    });
                    that.state.set(set_state);
                    that.navigate(state.data.folder, 'stateless');
                }
            });
            this.addEvent('afterNavigate', function(path, type) {
                if (type !== 'stateless' && that.history) {
                    var obj = {
                            folder: that.active,
                            container: that.container ? that.container.slug : null
                        },
                        state = this.state.getData();

                    Object.each(state, function(value, key) {
                        if (typeof value !== 'function' && typeof value !== 'undefined') {
                            obj[key] = value;
                        }
                    });

                    var method = type === 'initial' ? 'replaceState' : 'pushState';
                    var url = that.getUrl().setData(obj, true).set('fragment', '').toString();

                    that.history[method](obj, null, url);
                }
            });
        }

        this.fireEvent('afterSetHistory');
    },
    /**
     * type can be 'stateless' for no state or 'initial' to use replaceState
     * response can be set if you want to set the results without an AJAX request.
     */
    navigate: function(path, type, revalidate_cache, response) {
        this.fireEvent('beforeNavigate', [path, type]);
        if (path !== undefined) {
            if (this.active) {
                // Reset offset if we are changing folders
                this.state.set('offset', 0);
            }
            this.active = path == '/' ? '' : path;
        }

        this.grid.reset();
        this.grid.spin();

        var parts = this.active.split('/'),
            name = parts[parts.length ? parts.length-1 : 0],
            folder = parts.slice(0, parts.length-1).join('/'),
            that = this,
            url_builder = function(url) {
                if (revalidate_cache) {
                    url['revalidate_cache'] = 1;
                }
                return this.createRoute(url);
            }.bind(this),
            handleResponse = function(response) {
                if (response) {
                    if (response.status !== false) {
                        Object.each(response.entities, function(item) {
                            if (!item.baseurl) {
                                item.baseurl = that.baseurl;
                            }
                        });

                        that.grid.insertRows(response.entities);

                        if (!response.partial) {
                            that.grid.unspin();
                            that.response = response;
                            that.fireEvent('afterSelect', response);
                        }
                    } else if (response.error) {
                        alert(response.error);
                    }
                }
            };

        this.folder = new Files.Folder({'folder': folder, 'name': name});

        if (response) {
            handleResponse(response);
            this.grid.unspin();
        } else {
            this.fetch(this.folder.path, url_builder)
                .done(handleResponse).progress(handleResponse);
        }

        if (this.cookie) {
            var data = kQuery.extend(true, {}, this.state.data);
            data.folder = this.active;
            Cookie.write(this.cookie+'.state', JSON.encode(data), this.options.cookie);
        }

        this.fireEvent('afterNavigate', [path, type]);
    },
    fetch: function(path, url_builder) {
        var self = this,
            deferred = kQuery.Deferred(),
            fail = function(xhr) {
                var response = JSON.decode(xhr.responseText, true);

                if (response && response.error) {
                    alert(response.error);
                }
            },
            query = Object.append({view: 'nodes', folder: path}, this.state.getData());

        if (this.ajax_cache) {
            this.ajax_cache.abort();
        }

        if (typeof query.limit !== 'undefined' && query.limit !== 0) {
            this.ajax_cache = kQuery.getJSON(url_builder(query)).fail(fail);
            return this.ajax_cache;
        }

        query.limit = 100;

        var done = function(response) {
            if (!response || typeof response.entities === 'undefined' || typeof response.meta === 'undefined') {
                deferred.reject('');

                return;
            }

            if (response.meta.offset + response.entities.length < response.meta.total) {
                response.partial = true;
                deferred.notify(response);

                query.offset = response.meta.offset+response.meta.limit;
                self.ajax_cache = kQuery.getJSON(url_builder(query)).done(done).fail(fail);
            } else {
                response.completed = true;
                deferred.resolve(response);
            }
        };

        self.ajax_cache = kQuery.getJSON(url_builder(query)).done(done).fail(fail);

        return deferred.promise();
    },

    setContainer: function(container) {
        var setter = function(item) {
            this.fireEvent('beforeSetContainer', {container: item});

            this.container = item;
            this.baseurl = Files.sitebase + '/' + item.relative_path;

            this.active = '';

            if (this.uploader) {
                if (this.container.parameters.allowed_extensions) {
                    this.uploader.settings.filters = [
                        {title: Koowa.translate('All Files'), extensions: this.container.parameters.allowed_extensions.join(',')}
                    ];
                }

                if (this.container.parameters.maximum_size) {
                    this.uploader.settings.max_file_size = this.container.parameters.maximum_size;
                    var max_size = document.id('upload-max-size');
                    if (max_size) {
                        max_size.set('html', new Files.Filesize(this.container.parameters.maximum_size).humanize());
                    }
                }
            }

            if (this.container.parameters.thumbnails !== true) {
                this.options.thumbnails = false;
            } else {
                this.state.set('thumbnails', true);
            }

            if (this.options.types !== null) {
                this.options.grid.types = this.options.types;
                this.state.set('types', this.options.types);
            }

            if (this.options.folder_dialog && document.getElement(this.options.folder_dialog.view) && document.getElement(this.options.folder_dialog.view).getElement('form')) {
                this.setFolderDialog();
            }

            this.fireEvent('afterSetContainer', {container: item});

            this.setTree();

            this.active = this.options.active || '';
            this.options.active = '';

            if (typeof this.options.initial_response === 'string') {
                this.options.initial_response = JSON.decode(this.options.initial_response);
            }

            this.navigate(this.active, 'initial', false, this.options.initial_response);
        }.bind(this);

        if (typeof container === 'string') {
            new Request.JSON({
                url: this.createRoute({view: 'container', slug: container, container: false}),
                method: 'get',
                onSuccess: function(response) {
                    setter(response.entities[0]);
                }.bind(this)
            }).send();
        } else {
            setter(container);
        }
    },
    setPaginator: function() {
        this.fireEvent('beforeSetPaginator');

        var opts = this.options.paginator,
            state = this.state;

        Object.append(opts, {
            'state' : state,
            'onClickPage': function(el) {
                this.state.set('limit', el.get('data-limit'));
                this.state.set('offset', el.get('data-offset'));

                this.navigate();
            }.bind(this),
            'onChangeLimit': function(limit) {
                this.state.set('limit', limit);

                // Recalculate offset
                var total = Files.app.paginator.values.total,
                    offset = Files.app.paginator.values.offset;

                if (total) {
                    offset = limit ? Math.floor((offset/limit)*limit) : 0;
                }

                this.state.set('offset', offset);

                this.navigate();
            }.bind(this)
        });
        this.paginator = new Files.Paginator(opts.element, opts);


        var that = this;
        that.addEvent('afterSelect', function(response) {
            that.paginator.setData({
                limit: response.meta.limit,
                offset: response.meta.offset,
                total: response.meta.total
            });
            that.paginator.setValues();
        });

        this.fireEvent('afterSetPaginator');
    },
    setGrid: function() {
        this.fireEvent('beforeSetGrid');

        var that = this,
            opts = this.options.grid,
            key = this.cookie+'.grid.layout';

        if (this.cookie && Cookie.read(key)) {
            opts.layout = Cookie.read(key);
        }

        Object.append(opts, {
            'onClickFolder': function(e) {
                var target = document.id(e.target),
                    node = target.getParent('.files-node-shadow') || target.getParent('.files-node'),
                    path = node.retrieve('row').path;
                if (path) {
                    this.navigate(path);
                }
            }.bind(this),
            'onClickImage': function(e) {
                var target = document.id(e.target),
                    node = target.getParent('.files-node-shadow') || target.getParent('.files-node'),
                    row = node.retrieve('row'),
                    img = that.createRoute({view: 'file', format: 'html', name: row.name, folder: row.folder});

                if (img) {
                    kQuery.magnificPopup.open({
                        items: {
                            src: img,
                            type: 'image'
                        }
                    });
                }
            },
            'onClickFile': function(e) {
                var target = document.id(e.target),
                    node = target.getParent('.files-node-shadow') || target.getParent('.files-node'),
                    row = node.retrieve('row'),
                    copy = Object.append({}, row);

                copy.template = 'file_preview';

                copy = copy.render();

                var element = kQuery(copy);
                element.addClass('mfp-hide koowa');
                kQuery.magnificPopup.open({
                    items: {
                        src: element,
                        type: 'inline'
                    }
                });
            },
            'onBeforeRenderObject': function(context) {
                var row = context.object;
                row.download_link = that.createRoute({view: 'file', format: 'html', name: row.name, folder: row.folder});
            }.bind(this),
            'onAfterSetLayout': function(context) {
                if (key) {
                    Cookie.write(key, context.layout, this.options.cookie);
                }
            }.bind(this)
        });
        this.grid = new Files.Grid(this.options.grid.element, opts);

        this.fireEvent('afterSetGrid');
    },
    setTree: function() {
        this.fireEvent('beforeSetTree');

        if (this.options.tree.enabled) {
            var opts = this.options.tree,
                that = this;

            opts = kQuery.extend(true, {}, {
                onSelectNode: function(node) {
                    if (node.id || node.url) {
                        var path = node && node.id ? node.id : '';
                        if (path != that.active) {
                            that.navigate(path);
                        }
                    }
                },
                root: {
                    text: this.options.root_text
                },
                initial_response: !!this.options.initial_response
            }, opts);
            this.tree = new Files.Tree(kQuery(opts.element), opts);
            this.tree.fromUrl(this.createRoute({view: 'folders', 'tree': '1', 'limit': '2000'}));

            this.addEvent('afterNavigate', function(path, type) {
                if(path !== undefined && (!type || (type != 'initial' && type != 'stateless'))) {
                    that.tree.selectPath(path);
                }
            });

            if (this.grid) {
                this.grid.addEvent('afterDeleteNode', function(context) {
                    var node = context.node;
                    if (node.type == 'folder') {
                        that.tree.removeNode(node.path);
                    }
                });
            }
        }

        this.fireEvent('afterSetTree');
    },
    /**
     * Create the folder dialog markup and link up events
     */
    setFolderDialog: function(){

        var self = this;

        this._folder_dialog = {
            view: document.getElement(this.options.folder_dialog.view),
            input: document.getElement(this.options.folder_dialog.input),
            open_button: document.getElement(this.options.folder_dialog.open_button),
            create_button: document.getElement(this.options.folder_dialog.create_button)
        };

        if(this.options.folder_dialog.onInit) {
            this.options.folder_dialog.onInit.call(this, this._folder_dialog);
        }

        if (this._folder_dialog.open_button) {
            this._folder_dialog.open_button.addEvent('click', function(e) {
                e.stop();

                if (this.hasClass('unauthorized')) {
                    return;
                }

                Files.app.openFolderDialog();
            });
        }

        if (this._folder_dialog.view.getElement('form')) {
            this._folder_dialog.view.getElement('form').addEvent('submit', function(e){
                e.stop();

                self._folder_dialog.create_button.setProperty('disabled', 'disabled');

                if(self.options.folder_dialog.onSubmit) {
                    self.options.folder_dialog.onSubmit.call(self, self._folder_dialog);
                }
                var element = self._folder_dialog.input;
                var value = element.get('value').trim();
                if (value.length > 0) {
                    var folder = new Files.Folder({name: value, folder: Files.app.getPath()});
                    folder.add(function(response, responseText) {
                        if (response.status === false) {
                            return alert(response.error);
                        }
                        var el = response.entities[0];
                        var cls = Files[el.type.capitalize()];
                        var row = new cls(el);
                        Files.app.grid.insert(row);
                        Files.app.tree.appendNode({
                            id: row.path,
                            label: row.name
                        });

                        if(self.options.folder_dialog.onCreate) {
                            self.options.folder_dialog.onCreate.call(self, self._folder_dialog);
                        }

                        self.closeFolderDialog();
                    }, null, function() {
                        self._folder_dialog.create_button.setProperty('disabled', false);
                    });
                }
            });
        }

    },
    /**
     * Opens the folder dialog, using the customizable control handle, if the instance exists
     * @return returns a boolean indicating wether there's a folder dialog active
     */
    openFolderDialog: function(){

        if(this.options.folder_dialog) {
            this.options.folder_dialog.onOpen.call(this, this._folder_dialog);
        }

        return !!this.options.folder_dialog;
    },
    /**
     * Closes the folder dialog, using the customizable control handle, if the instance exists
     * @return returns a boolean indicating wether there's a folder dialog active
     */
    closeFolderDialog: function(){

        if(this.options.folder_dialog) {
            this.options.folder_dialog.onClose.call(this, this._folder_dialog);
        }

        return !!this.options.folder_dialog;
    },
    /**
     * Sets the IE Flash workaround and FLOC fix, and hooks the markup with events for the uploader dialog
     */
    setUploaderDialog: function(){
        var self   = this,
            button = document.getElement(this.options.uploader_dialog.button),
            view   = document.getElement(this.options.uploader_dialog.view);

        if (view) {
            this._tmp_uploader = new Element('div', {style: 'display:none'}).inject(document.body);

            document.getElement(this.options.uploader_dialog.view).getParent().inject(this._tmp_uploader).setStyle('visibility', '');
        }

        if (button) {
            button.addEvent('click', function(e){
                e.stop();

                if (this.hasClass('unauthorized')) {
                    return;
                }

                self.openUploaderDialog();
            });
        }
    },
    /**
     * Opens up the Uploader dialog and performs IE flash workaround
     * @return returns a boolean indicating wether there's a uploader dialog active
     */
    openUploaderDialog: function(){

        if(this.uploader) {
            var self = this, handleClose = function(){
                document.getElement(self.options.uploader_dialog.view).getParent().inject(self._tmp_uploader);
                SqueezeBox.removeEvent('close', handleClose);
            };
            SqueezeBox.addEvent('close', handleClose);
            SqueezeBox.open(document.getElement(self.options.uploader_dialog.view).getParent(), {
                handler: 'adopt',
                size: {x: 700, y: document.getElement(self.options.uploader_dialog.view).getParent().measure(function(){
                    this.setStyle('width', 700);
                    var height = this.getSize().y;
                    this.setStyle('width', '');
                    return height;
                })}
            });
            window.addEvent('QueueChanged', this._changeUploaderDialogHeight.bind(this));
        }

        return !!this.uploader;
    },
    /**
     * Closes the Uploader dialog and performs IE flash workaround
     * @return returns a boolean indicating wether there's a uploader dialog active
     */
    closeUploaderDialog: function(){

        if(this.uploader) {
            SqueezeBox.close();
            window.removeEvent('QueueChanged', this._changeUploaderDialogHeight);
        }

        return !!this.uploader;
    },
    /**
     * Updates the uploader dialog height
     * @private
     */
    _changeUploaderDialogHeight: function(){
        var height = document.getElement(this.options.uploader_dialog.view).getParent().scrollHeight;
        SqueezeBox.resize({x: 700, y: height});
    },
    getUrl: function() {
        return new URI(window.location.href);
    },
    getPath: function() {
        return this.active;
    },
    setThumbnails: function() {
        this.setDimensions(true);
        var nodes = this.grid.nodes,
            that = this;
        if (nodes.getLength()) {
            nodes.each(function(node) {
                if (node.filetype !== 'image') {
                    return;
                }
                var name = node.name;

                var img = node.element.getElement('img.image-thumbnail');
                if (img) {
                    img.addEvent('load', function(){
                        this.addClass('loaded');
                    });
                    img.set('src', node.thumbnail ? node.thumbnail : Files.blank_image);

                    (node.element.getElement('.files-node') || node.element).addClass('loaded').removeClass('loading');

                    if(window.sessionStorage) {
                        sessionStorage[node.image.toString()] = img.get('src');
                    }
                }
            });
        }

    },
    setDimensions: function(force){

        if(!this._cached_grid_width) this._cached_grid_width = 0;

        //Only fire if the cache have changed
        if(this._cached_grid_width != this.grid.root.element.getSize().x || force) {
            var width = this.grid.root.element.getSize().x,
                factor = width/(this.grid.options.icon_size.toInt()+40),
                limit = Math.min(Math.floor(factor), this.grid.nodes.getLength());

            this.grid.root.element.getElements('.files-node-shadow').each(function(element, i, elements){
                element.setStyle('width', (100/limit)+'%');
            }, this);

            this._cached_grid_width = this.grid.root.element.getSize().x;
        }
    },
    setPathway: function() {
        this.fireEvent('beforeSetPathway');

        var pathway = new Files.Pathway(this.options.pathway);
        this.addEvent('afterSetTitle', pathway.setPath.bind(pathway, this));

        this.fireEvent('afterSetPathway');
    },
    setTitle: function(title) {
        this.fireEvent('beforeSetTitle', {title: title});

        this.title = title;

        this.fireEvent('afterSetTitle', {title: title});
    },
    createRoute: function(query) {
        query = Object.merge({}, this.options.router.defaults, query || {});

        if (query.container !== false && !query.container && this.container) {
            query.container = this.container.slug;
        } else {
            delete query.container;
        }

        if (query.format == 'html') {
            delete query.format;
        }

        var route = '?'+new Hash(query).filter(function(value, key) {
            return typeof value !== 'function';
        }).toQueryString();

        return this.options.base_url ? this.options.base_url+route : route;
    }
});
