(function ($) {

	'use strict';

	var Emsh_ElementorSectionsData = window.Emsh_ElementorSectionsData || {},
		Emsh_ElementorSectionsEditor,
		Emsh_ElementorSectionsEditorViews;

	Emsh_ElementorSectionsEditorViews = {

		ModalLayoutView: null,
		ModalHeaderView: null,
		ModalLoadingView: null,
		ModalSearchView: null,
		ModalBodyView: null,
		ModalErrorView: null,
		LibraryCollection: null,
		ModalCollectionView: null,
		ModalTabsCollection: null,
		ModalTabsCollectionView: null,
		FiltersCollectionView: null,
		FiltersItemView: null,
		ModalTabsItemView: null,
		ModalTemplateItemView: null,
		ModalInsertTemplateBehavior: null,
		ModalTemplateModel: null,
		CategoriesCollection: null,
		ModalHeaderLogo: null,
		TabModel: null,
		CategoryModel: null,
		TemplatesEmptyView: null,
		TemplateSearchCollectionView: null,

		init: function () {
			var self = this;

			self.ModalTemplateModel = Backbone.Model.extend({
				defaults: {
					template_id: 0,
					title: '',
					thumbnail: '',
					source: '',
					categories: []
				}
			});

			self.ModalHeaderView = Marionette.LayoutView.extend({

				id: 'emshelementor-template-modal-header',
				template: '#tmpl-emshelementor-template-modal-header',

				ui: {
					closeModal: '#emshelementor-template-modal-header-close-modal'
				},

				events: {
					'click @ui.closeModal': 'onCloseModalClick'
				},

				regions: {
					headerLogo: '#emshelementor-template-modal-header-logo-area',
					headerTabs: '#emshelementor-template-modal-header-tabs',
					headerActions: '#emshelementor-template-modal-header-actions'
				},

				onCloseModalClick: function () {
					Emsh_ElementorSectionsEditor.closeModal();
				}

			});

			self.TabModel = Backbone.Model.extend({
				defaults: {
					slug: '',
					title: ''
				}
			});

			self.LibraryCollection = Backbone.Collection.extend({
				model: self.ModalTemplateModel
			});

			self.ModalTabsCollection = Backbone.Collection.extend({
				model: self.TabModel
			});

			self.CategoryModel = Backbone.Model.extend({
				defaults: {
					slug: '',
					title: ''
				}
			}); 

			self.CategoriesCollection = Backbone.Collection.extend({
				model: self.CategoryModel
			});

			self.ModalHeaderLogo = Marionette.ItemView.extend({

				template: '#tmpl-emshelementor-template-modal-header-logo',

				id: 'emshelementor-template-modal-header-logo'

			});

			self.ModalBodyView = Marionette.LayoutView.extend({

				id: 'emshelementor-template-library-content',

				className: function () {
					return 'library-tab-' + Emsh_ElementorSectionsEditor.getTab();
				},

				template: '#tmpl-emshelementor-template-modal-content',

				regions: {
					contentTemplates: '.emshelementor-templates-list',
					contentFilters: '.emshelementor-filters-list',
					contentSearch: '#elementor-template-library-filter-text-wrapper',
				}

			});

			self.TemplatesEmptyView = Marionette.LayoutView.extend({

				id: 'emshelementor-template-modal-empty',

				template: '#tmpl-emshelementor-template-modal-empty',

				ui: {
					title: '.elementor-template-library-blank-title',
				},

				regions: {
					contentTemplates: '.emshelementor-templates-list',
					contentFilters: '.emshelementor-filters-list',
					contentSearch: '#elementor-template-library-filter-text-wrapper',
				}

			});

			self.ModalInsertTemplateBehavior = Marionette.Behavior.extend({
				ui: {
					insertButton: '.emshelementor-template-insert'
				},

				events: {
					'click @ui.insertButton': 'onInsertButtonClick'
				},

				onInsertButtonClick: async function (e) {
					var self = this;
					var emshswal = Swal.mixin({
						customClass: {
						  container: 'emsh-swal-container',
						},
					});

					var pluginsjson = $(e.currentTarget).attr('data-required-plugins');
					var shouldProceed = false;
					if (pluginsjson && pluginsjson.charAt(0) === '{') {
						var requiredPlugins = JSON.parse(pluginsjson);
						var swalHtml = '';
						if ( ('names' in requiredPlugins) && ('urls' in requiredPlugins) && requiredPlugins.names.length ) {
							for ( var i = 0; i < requiredPlugins.names.length; i++ ) {
								var name = requiredPlugins.names[i];
								var url = requiredPlugins.urls[i];
								swalHtml += `<li><a target="_blank" href="${url}">${name}</a></li>`;
							}
						}
						else {
							shouldProceed = true;
						}

						if ( swalHtml.length ) {
							
							swalHtml = `<p class="pleaseconfirm">${Emsh_ElementorSectionsData.pleaseconfirm}</p><ul class="required_plugins_list">${swalHtml}</ul>`;
							
							var result = await emshswal.fire({
								title: Emsh_ElementorSectionsData.requiredplugins,
								icon: 'info',
								showCancelButton: true,
								confirmButtonText: Emsh_ElementorSectionsData.confirm,
								cancelButtonText: Emsh_ElementorSectionsData.cancel,
								target: '#emshelementor-template-modal .dialog-lightbox-widget-content',
								html: swalHtml,
							});

							if (result.isConfirmed) {
								shouldProceed = true;
							}
							else {
								shouldProceed = false;
							}
							
						}
						else {
							shouldProceed = true;
						}
					}
					else {
						shouldProceed = true;
					}
					
					if ( shouldProceed ) {
						// insert
						var templateModel = self.view.model,
						options = {};

						Emsh_ElementorSectionsEditor.layout.showLoadingView();

						// this ajax request is for requesting the template from server site and saving it into the client site
						// $.ajax({
						// 	url: ajaxurl,
						// 	type: 'post',
						// 	dataType: 'json',
						// 	data: {
						// 		action: 'emsh_elementor_inner_template',
						// 		template: templateModel.attributes,
						// 		tab: Emsh_ElementorSectionsEditor.getTab(),
						// 		site_url: Emsh_ElementorSectionsEditor.site_url,
						// 		license_key: Emsh_ElementorSectionsEditor.license_key
						// 	}
						// });

						
						var args = {
							model: templateModel
						}
						var autoImportSettings = elementor.config.document.remoteLibrary.autoImportSettings,
							model = args.model;
						var _args$withPageSetting = args.withPageSettings,
							withPageSettings = _args$withPageSetting === void 0 ? null : _args$withPageSetting;
						if (autoImportSettings) {
							withPageSettings = true;
						}
						if (null === withPageSettings && model.get('hasPageSettings')) {

							var emshswal = Swal.mixin({
								customClass: {
								  container: 'emsh-swal-container',
								},
							});

							var confirmresult = await emshswal.fire({
								title: Emsh_ElementorSectionsData.applypagesettings,
								text: Emsh_ElementorSectionsData.thiswilloverride,
								icon: 'warning',
								showCancelButton: true,
								confirmButtonColor: '#3085d6',
								cancelButtonColor: '#d33',
								confirmButtonText: Emsh_ElementorSectionsData.apply,
								cancelButtonText: Emsh_ElementorSectionsData.dontapply,
								target: '#emshelementor-template-modal .dialog-lightbox-widget-content',
							});

							if ( confirmresult.isConfirmed ) {
								withPageSettings = true;
							}
							else {
								withPageSettings = false;
							}
						}

						// another request for inserting
						Emsh_ElementorSectionsEditor.requestTemplateData(model, withPageSettings);

					}
				}
			});

			self.FiltersItemView = Marionette.ItemView.extend({

				template: '#tmpl-emshelementor-template-modal-filters-item',

				className: function () {
					return 'emshelementor-template-filter-item';
				},

				ui: function () {
					return {
						filterLabels: '.emshelementor-template-filter-label'
					};
				},

				events: function () {
					return {
						'click @ui.filterLabels': 'onFilterClick'
					};
				},

				onFilterClick: function (event) {

					var $clickedInput = jQuery(event.target);
					Emsh_ElementorSectionsEditor.setFilter('category', $clickedInput.val());
				}

			});

			self.TemplateSearchCollectionView = Marionette.CompositeView.extend({

				template: '#tmpl-emshelementor-template-modal-search-item',
				id: 'emshelementor-template-modal-search-item',

				ui: function () {
					return {
						textFilter: '#elementor-template-library-filter-text',
					};
				},

				events: function () {
					return {
						'input @ui.textFilter': 'onTextFilterInput',
					};
				},

				onTextFilterInput: function onTextFilterInput( childModel ) {

					var searchText = this.ui.textFilter.val();

					Emsh_ElementorSectionsEditor.setFilter('text', searchText);
				},

			});

			self.ModalTabsItemView = Marionette.ItemView.extend({

				template: '#tmpl-emshelementor-template-modal-tabs-item',

				className: function () {
					return 'elementor-template-library-menu-item';
				},

				ui: function () {
					return {
						tabsLabels: 'label',
						tabsInput: 'input'
					};
				},

				events: function () {
					return {
						'click @ui.tabsLabels': 'onTabClick'
					};
				},

				onRender: function () {
					if (this.model.get('slug') === Emsh_ElementorSectionsEditor.getTab()) {
						this.ui.tabsInput.attr('checked', 'checked');
					}
				},

				onTabClick: function (event) {

					var $clickedInput = jQuery(event.target);
					Emsh_ElementorSectionsEditor.setTab($clickedInput.val());
				}

			});

			self.FiltersCollectionView = Marionette.CompositeView.extend({

				id: 'emshelementor-template-library-filters',

				template: '#tmpl-emshelementor-template-modal-filters',

				childViewContainer: '#emshelementor-modal-filters-container',

				getChildView: function (childModel) {
					return self.FiltersItemView;
				}

			});

			self.ModalTabsCollectionView = Marionette.CompositeView.extend({

				template: '#tmpl-emshelementor-template-modal-tabs',

				childViewContainer: '#emshelementor-modal-tabs-items',

				initialize: function () {
					this.listenTo(Emsh_ElementorSectionsEditor.channels.layout, 'tamplate:cloned', this._renderChildren);
				},

				getChildView: function (childModel) {
					return self.ModalTabsItemView;
				}

			});

			self.ModalTemplateItemView = Marionette.ItemView.extend({

				template: '#tmpl-emshelementor-template-modal-item',

				className: function () {

					var urlClass = ' emshelementor-template-has-url',
						sourceClass = ' elementor-template-library-template-';

					sourceClass += 'remote';

					return 'elementor-template-library-template' + sourceClass + urlClass;
				},

				ui: function () {
					return {
						previewButton: '.elementor-template-library-template-preview',
					};
				},

				behaviors: {
					insertTemplate: {
						behaviorClass: self.ModalInsertTemplateBehavior
					}
				}
			});

			self.ModalCollectionView = Marionette.CompositeView.extend({

				template: '#tmpl-emshelementor-template-modal-templates',

				id: 'emshelementor-template-library-templates',

				childViewContainer: '#emshelementor-modal-templates-container',

				emptyView: function emptyView() {

					return new self.TemplatesEmptyView();
				},

				initialize: function () {

					this.listenTo(Emsh_ElementorSectionsEditor.channels.templates, 'filter:change', this._renderChildren);
				},

				filter: function (childModel) {

					var filter = Emsh_ElementorSectionsEditor.getFilter('category');
					var searchText = Emsh_ElementorSectionsEditor.getFilter('text');

					if (!filter && !searchText) {
						return true;
					}

					if (filter && !searchText) {
						return _.contains(childModel.get('categories'), filter);
					}

					if (searchText && !filter) {
						if (childModel.get('title').toLowerCase().indexOf(searchText) >= 0) {
							return true;
						}
					}

					if (searchText && filter) {
						return _.contains(childModel.get('categories'), filter) && childModel.get('title').toLowerCase().indexOf(searchText) >= 0;
					}

				},

				getChildView: function (childModel) {
					return self.ModalTemplateItemView;
				},

				onRenderCollection: function () {

					var container = this.$childViewContainer,
						items = this.$childViewContainer.children(),
						tab = Emsh_ElementorSectionsEditor.getTab();

					if ('emsh_elementor_template_groups_page' === tab || 'local' === tab) {
						return;
					}

					// Wait for thumbnails to be loaded.
					container.imagesLoaded(function () { }).done(function () {
						self.masonry.init({
							container: container,
							items: items
						});
					});
				}

			});

			self.ModalLayoutView = Marionette.LayoutView.extend({

				el: '#emshelementor-template-modal',

				regions: Emsh_ElementorSectionsData.modalRegions,

				initialize: function () {

					this.getRegion('modalHeader').show(new self.ModalHeaderView());
					this.listenTo(Emsh_ElementorSectionsEditor.channels.tabs, 'filter:change', this.switchTabs);

				},

				switchTabs: function () {
					this.showLoadingView();
					Emsh_ElementorSectionsEditor.requestTemplates(Emsh_ElementorSectionsEditor.getTab());
				},

				getHeaderView: function () {
					return this.getRegion('modalHeader').currentView;
				},

				getContentView: function () {
					return this.getRegion('modalContent').currentView;
				},

				showLoadingView: function () {
					this.modalContent.show(new self.ModalLoadingView());
				},

				showSearchView: function() {
					this.modalContent.show(new self.ModalSearchView());
				},

				showError: function () {
					this.modalContent.show(new self.ModalErrorView());
					jQuery('.emsh-errormsg').text(Emsh_ElementorSectionsEditor.error_msg);
				},

				showTemplatesView: function (templatesCollection, categoriesCollection ) {

					if( 0 !== templatesCollection.length ) {
						this.getRegion('modalContent').show(new self.ModalBodyView());
						var contentView = this.getContentView(),
							header = this.getHeaderView();

						Emsh_ElementorSectionsEditor.collections.tabs = new self.ModalTabsCollection(Emsh_ElementorSectionsEditor.getTabs());

						header.headerTabs.show(new self.ModalTabsCollectionView({
							collection: Emsh_ElementorSectionsEditor.collections.tabs
						}));

						contentView.contentTemplates.show(new self.ModalCollectionView({
							collection: templatesCollection
						}));

						contentView.contentFilters.show(new self.FiltersCollectionView({
							collection: categoriesCollection
						}));

						contentView.contentSearch.show(new self.TemplateSearchCollectionView());

					} else {
						this.getRegion('modalContent').show(new self.TemplatesEmptyView());
					}

				}

			});

			self.ModalLoadingView = Marionette.ItemView.extend({
				id: 'emshelementor-template-modal-loading',
				template: '#tmpl-emshelementor-template-modal-loading'
			});

			self.ModalErrorView = Marionette.ItemView.extend({
				id: 'emshelementor-template-modal-error',
				template: '#tmpl-emshelementor-template-modal-error'
			});

			self.ModalSearchView = Marionette.ItemView.extend({
				id: 'emshelementor-template-modal-search',
				template: '#tmpl-emshelementor-template-modal-search',

				ui: {
					browseBtn: '.emsh-browse-btn'
				},

				events: {
					'click @ui.browseBtn': 'onBrowseBtnClick'
				},

				onBrowseBtnClick: function(e) {
					var selectedVal = jQuery('.site-selector-wrapper select').val();
					if ( selectedVal ) {
						Emsh_ElementorSectionsEditor.license_key = jQuery('.site-selector-wrapper select option[value="'+selectedVal+'"]').attr('data-licensekey');
						Emsh_ElementorSectionsEditor.site_url = jQuery('.site-selector-wrapper select option[value="'+selectedVal+'"]').attr('data-siteurl');

						Emsh_ElementorSectionsEditor.setTab(Emsh_ElementorSectionsEditor.defaultTab, true);
						Emsh_ElementorSectionsEditor.requestTemplates(Emsh_ElementorSectionsEditor.defaultTab);

						$('.emsh-source').show().find('.emsh-source-url').text(Emsh_ElementorSectionsEditor.site_url);
					}
				}
			});

		},

		masonry: {

			self: {},
			elements: {},

			init: function (settings) {

				var self = this;
				self.settings = $.extend(self.getDefaultSettings(), settings);
				self.elements = self.getDefaultElements();

				self.run();
			},

			getSettings: function (key) {
				if (key) {
					return this.settings[key];
				} else {
					return this.settings;
				}
			},

			getDefaultSettings: function () {
				return {
					container: null,
					items: null,
					columnsCount: 3,
					verticalSpaceBetween: 30
				};
			},

			getDefaultElements: function () {
				return {
					$container: jQuery(this.getSettings('container')),
					$items: jQuery(this.getSettings('items'))
				};
			},

			run: function () {
				var heights = [],
					distanceFromTop = this.elements.$container.position().top,
					settings = this.getSettings(),
					columnsCount = settings.columnsCount;

				distanceFromTop += parseInt(this.elements.$container.css('margin-top'), 10);

				this.elements.$container.height('');

				this.elements.$items.each(function (index) {
					var row = Math.floor(index / columnsCount),
						indexAtRow = index % columnsCount,
						$item = jQuery(this),
						itemPosition = $item.position(),
						itemHeight = $item[0].getBoundingClientRect().height + settings.verticalSpaceBetween;

					if (row) {
						var pullHeight = itemPosition.top - distanceFromTop - heights[indexAtRow];
						pullHeight -= parseInt($item.css('margin-top'), 10);
						pullHeight *= -1;
						$item.css('margin-top', pullHeight + 'px');
						heights[indexAtRow] += itemHeight;
					} else {
						heights.push(itemHeight);
					}
				});

				this.elements.$container.height(Math.max.apply(Math, heights));
			}
		}

	};

	Emsh_ElementorSectionsEditor = {
		modal: false,
		layout: false,
		collections: {},
		tabs: {},
		defaultTab: '',
		channels: {},
		atIndex: null,
		site_url: '',
		license_key: '',
		error_msg: '',
		last_fetched_site: '',

		init: function () {

			window.elementor.on(
				'document:loaded',
				window._.bind(Emsh_ElementorSectionsEditor.onPreviewLoaded, Emsh_ElementorSectionsEditor)
			);

			Emsh_ElementorSectionsEditorViews.init();

		},

		onPreviewLoaded: function () {

			this.initEmsh_ElementorSectionsTempsButton();

			window.elementor.$previewContents.on(
				'click.addEmsh_ElementorSectionsTemplate',
				'.emsh-add-section-btn',
				_.bind(this.showTemplatesModal, this)
			);

			this.channels = {
				templates: Backbone.Radio.channel('EMSHELEMENTOR_EDITOR:templates'),
				tabs: Backbone.Radio.channel('EMSHELEMENTOR_EDITOR:tabs'),
				layout: Backbone.Radio.channel('EMSHELEMENTOR_EDITOR:layout'),
			};

			this.tabs = Emsh_ElementorSectionsData.tabs;
			this.defaultTab = Emsh_ElementorSectionsData.defaultTab;

		},

		initEmsh_ElementorSectionsTempsButton: function () {

			setTimeout(function () {
				var $addNewSection = window.elementor.$previewContents.find('.elementor-add-new-section'),
					addEmsh_ElementorSectionsTemplate = "<div class='elementor-add-section-area-button emsh-add-section-btn' title='Add Elementor Sections Template'><img src='"+Emsh_ElementorSectionsData.icon+"'></div>",
					$addEmsh_ElementorSectionsTemplate;

				if ($addNewSection.length) {
					$addEmsh_ElementorSectionsTemplate = $(addEmsh_ElementorSectionsTemplate).prependTo($addNewSection);
				}
			
				window.elementor.$previewContents.on(
					'click.addEmsh_ElementorSectionsTemplate',
					'.elementor-editor-section-settings .elementor-editor-element-add',
					function () {

						var $this = $(this),
							$section = $this.closest('.elementor-top-section'),
							modelID = $section.data('model-cid');



						if (-1 !== Emsh_ElementorSectionsData.Elementor_Version.indexOf('3.0.')) {
							if (window.elementor.previewView.collection.length) {
								$.each(window.elementor.previewView.collection.models, function (index, model) {
									if (modelID === model.cid) {
										Emsh_ElementorSectionsEditor.atIndex = index;
									}
								});
							}
						} else {
							if (window.elementor.sections.currentView.collection.length) {
								$.each(window.elementor.sections.currentView.collection.models, function (index, model) {
									if (modelID === model.cid) {
										Emsh_ElementorSectionsEditor.atIndex = index;
									}
								});
							}
						}


						setTimeout(function () {
							var $addNew = $section.prev('.elementor-add-section').find('.elementor-add-new-section');
							$addNew.prepend(addEmsh_ElementorSectionsTemplate);
						}, 100);

					}
				);
            }, 100);
		},

		requestTemplateData: function(model, withPageSettings) {
			elementor.templates.requestTemplateContent(model.get('source'), model.get('template_id'), {
				data: {
					with_page_settings: withPageSettings,
					tab: Emsh_ElementorSectionsEditor.getTab(),
					site_url: Emsh_ElementorSectionsEditor.site_url,
					license_key: Emsh_ElementorSectionsEditor.license_key
				},
				success: function success(data) {
					// Clone the `modalConfig.importOptions` because it deleted during the closing.
					var importOptions = jQuery.extend({}, elementor.templates.modalConfig.importOptions);
					importOptions.withPageSettings = withPageSettings;
			
					// before closing, remove the loading view so that after opening the modal again, the loading view is not there by default
					Emsh_ElementorSectionsEditor.requestTemplates(Emsh_ElementorSectionsEditor.getTab());
					// Emsh_ElementorSectionsEditor.closeModal();
					$e.run('document/elements/import', {
						model: model,
						data: data,
						options: importOptions
					});
				},
				error: function error(data) {
					Emsh_ElementorSectionsEditor.error_msg = Emsh_ElementorSectionsData.cantcommunicate;
					Emsh_ElementorSectionsEditor.layout.showError();
				},
				complete: function complete() {
					Emsh_ElementorSectionsEditor.closeModal();
				}
			});
		},

		getFilter: function (name) {

			return this.channels.templates.request('filter:' + name);
		},

		setFilter: function (name, value) {
			this.channels.templates.reply('filter:' + name, value);
			this.channels.templates.trigger('filter:change');
		},

		getTab: function () {
			return this.channels.tabs.request('filter:tabs');
		},

		setTab: function (value, silent) {

			this.channels.tabs.reply('filter:tabs', value);

			if (!silent) {
				this.channels.tabs.trigger('filter:change');
			}

		},

		getTabs: function () {

			var tabs = [];

			_.each(this.tabs, function (item, slug) {
				tabs.push({
					slug: slug,
					title: item.title
				});
			});

			return tabs;
		},

		showTemplatesModal: function () {

			this.getModal().show();

			if (!this.layout) {
				this.layout = new Emsh_ElementorSectionsEditorViews.ModalLayoutView();
				this.layout.showSearchView();
			}

		},

		requestTemplates: function (tabName) {

			if( '' === tabName ) {
				return;
			}

			var self = this,
				tab = self.tabs[tabName];

			self.setFilter('category', false);

			if (('backup' in tab.data) && tab.data.backup.templates && tab.data.backup.categories && self.site_url === self.last_fetched_site) {
				self.layout.showTemplatesView(tab.data.backup.templates, tab.data.backup.categories);
			} else {
				self.layout.showLoadingView();
				
				$.ajax({
					url: ajaxurl,
					type: 'get',
					dataType: 'json',
					data: {
						action: 'emsh_elementor_get_templates',
						tab: tabName,
						site_url: self.site_url,
						license_key: self.license_key,
					},
					success: function (response) {
						if ( 'success' in response ) {

							if ( response.success ) {

								console.log("%cTemplates Retrieved Successfully!!", "color: #7a7a7a; background-color: #eee;");
		
								var templates = new Emsh_ElementorSectionsEditorViews.LibraryCollection(response.data.templates),
									categories = new Emsh_ElementorSectionsEditorViews.CategoriesCollection(response.data.categories);
								self.last_fetched_site = self.site_url;
								self.tabs[tabName].data = {
									templates: templates,
									categories: categories,
									backup: { // also keep a backup so that we can restore them later
										templates: templates,
										categories: categories
									}
								};
		
								self.layout.showTemplatesView(templates, categories );
							}
							else {
								Emsh_ElementorSectionsEditor.error_msg = ('message' in response) ? response.message : Emsh_ElementorSectionsData.unknownresponse;
								self.layout.showError();
							}

						}
						else {
							Emsh_ElementorSectionsEditor.error_msg = Emsh_ElementorSectionsData.unknownresponse;
							self.layout.showError();
						}
					},
					error: function (err) {
						Emsh_ElementorSectionsEditor.error_msg = Emsh_ElementorSectionsData.cantcommunicate;
						self.layout.showError();
					}
				});
			}

		},

		closeModal: function () {
			this.getModal().hide();
		},

		getModal: function () {

			if (!this.modal) {
				this.modal = elementor.dialogsManager.createWidget('lightbox', {
					id: 'emshelementor-template-modal',
					className: 'elementor-templates-modal',
					closeButton: false
				});
			}

			return this.modal;

		}

	};

	$(window).on('elementor:init', Emsh_ElementorSectionsEditor.init);

	// on clicking source change button, display the site selector
	$(document).on('click', '.emsh-source-change', function(){
		Emsh_ElementorSectionsEditor.layout.showSearchView();
	});

	// on clicking template group layer, show the subtemplates view
	$(document).on('click', '.template-group-layer', function(e){
		var templateGroupId = $(this).attr('data-id');
		var templateGroupName = $(this).attr('data-name');
		if ( templateGroupId ) {
			// show loading view first
			Emsh_ElementorSectionsEditor.layout.showLoadingView();
			var tabName = 'emsh_template_groups';
				
			$.ajax({
				url: ajaxurl,
				type: 'get',
				dataType: 'json',
				data: {
					action: 'emsh_elementor_get_subtemplates',
					tab: tabName,
					template_group_id: templateGroupId,
					site_url: Emsh_ElementorSectionsEditor.site_url,
					license_key: Emsh_ElementorSectionsEditor.license_key,
				},
				success: function (response) {
					if ( 'success' in response ) {

						if ( response.success ) {

							console.log("%cTemplates Retrieved Successfully!!", "color: #7a7a7a; background-color: #eee;");
	
							var templates = new Emsh_ElementorSectionsEditorViews.LibraryCollection(response.data.templates),
								categories = new Emsh_ElementorSectionsEditorViews.CategoriesCollection(response.data.categories);
							Emsh_ElementorSectionsEditor.last_fetched_site = Emsh_ElementorSectionsEditor.site_url;
							Emsh_ElementorSectionsEditor.tabs[tabName].data = {
								templates: templates,
								categories: categories,
								...Emsh_ElementorSectionsEditor.tabs[tabName].data
							};
	
							Emsh_ElementorSectionsEditor.layout.showTemplatesView(templates, categories );

							// hide the sidebar
							$('.emshelementor-filters-list').hide();

							// show the template group title area
							$('.emsh-template-group-title').show().find('.emsh-template-group-title-text').text(templateGroupName);
						}
						else {
							Emsh_ElementorSectionsEditor.error_msg = ('message' in response) ? response.message : Emsh_ElementorSectionsData.unknownresponse;
							Emsh_ElementorSectionsEditor.layout.showError();
						}

					}
					else {
						Emsh_ElementorSectionsEditor.error_msg = Emsh_ElementorSectionsData.unknownresponse;
						Emsh_ElementorSectionsEditor.layout.showError();
					}
				},
				error: function (err) {
					Emsh_ElementorSectionsEditor.error_msg = Emsh_ElementorSectionsData.cantcommunicate;
					Emsh_ElementorSectionsEditor.layout.showError();
				}
			});

		}
	});

	// on clicking go back button in the subtemplate view
	$(document).on('click', '.emshgoback', function(){
		Emsh_ElementorSectionsEditor.requestTemplates(Emsh_ElementorSectionsEditor.getTab());
	});

})(jQuery);