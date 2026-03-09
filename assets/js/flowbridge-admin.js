/**
 * FlowBridge Admin JS
 *
 * Handles modal logic, AJAX calls, collapsible toggles, and entity configuration.
 *
 * @since 1.0.0
 * @package FlowBridge_N8N
 */

/* global jQuery, flowbridgeAdmin */
(function( $ ) {
	'use strict';

	var FlowBridge = {
		currentModal: null,
		currentEntityType: '',
		currentEntityKey: '',
		pendingFieldsRequest: null,

		init: function() {
			this.bindCollapsibles();
			this.bindEntityToggles();
			this.bindConfigureButtons();
			this.bindModalEvents();
			this.bindTestWebhook();
			this.bindWpDefaultToggle();
			this.bindLogs();
		},

		/* Collapsible sections */
		bindCollapsibles: function() {
			$( document ).on( 'click', '.flowbridge-collapsible-toggle', function() {
				var $toggle = $( this );
				var $content = $toggle.next( '.flowbridge-collapsible-content' );
				$toggle.toggleClass( 'open' );
				$content.slideToggle( 200 );
			});
		},

		/* Entity enable/disable toggles */
		bindEntityToggles: function() {
			$( document ).on( 'change', '.flowbridge-entity-toggle', function() {
				var $input = $( this );
				var entityType = $input.data( 'entity-type' );
				var entityKey = $input.data( 'entity-key' );
				var enabled = $input.is( ':checked' );

				$.post( flowbridgeAdmin.ajaxUrl, {
					action: 'flowbridge_n8n_toggle_entity',
					nonce: flowbridgeAdmin.nonce,
					entity_type: entityType,
					entity_key: entityKey,
					enabled: enabled ? 1 : 0
				});
			});
		},

		/* Configure buttons -> open modals */
		bindConfigureButtons: function() {
			var self = this;

			$( document ).on( 'click', '.flowbridge-configure-btn', function() {
				var $btn = $( this );
				var entityType = $btn.data( 'entity-type' );
				var entityKey = $btn.data( 'entity-key' );
				var entityLabel = $btn.data( 'entity-label' );

				self.openModal( entityType, entityKey, entityLabel );
			});
		},

		/* Open a configuration modal */
		openModal: function( entityType, entityKey, entityLabel ) {
			var self = this;
			var modalId;

			switch ( entityType ) {
				case 'post':
					modalId = '#flowbridge-modal-post';
					break;
				case 'taxonomy':
					modalId = '#flowbridge-modal-taxonomy';
					break;
				case 'user':
					modalId = '#flowbridge-modal-user';
					break;
				case 'cf7':
					modalId = '#flowbridge-modal-cf7';
					break;
				default:
					return;
			}

			this.currentModal = $( modalId );
			this.currentEntityType = entityType;
			this.currentEntityKey = entityKey;

			this.currentModal.find( '.flowbridge-modal-entity-label' ).text( entityLabel );
			this.currentModal.find( '.flowbridge-modal-status' ).text( '' ).removeClass( 'success error' );
			this.currentModal.find( '.flowbridge-preview-section' ).hide();

			/* Reset fields table */
			this.currentModal.find( '.flowbridge-fields-table tbody' ).html(
				'<tr class="flowbridge-fields-empty"><td colspan="6">' + flowbridgeAdmin.i18n.loading + '</td></tr>'
			);

			/* Reset event checkboxes, then restore saved events */
			this.currentModal.find( 'input[name="events[]"]' ).prop( 'checked', false );

			var savedConfig = this.getSavedConfig( entityType, entityKey );
			if ( savedConfig && savedConfig.events && savedConfig.events.length ) {
				$.each( savedConfig.events, function( i, evt ) {
					self.currentModal.find( 'input[name="events[]"][value="' + evt + '"]' ).prop( 'checked', true );
				});
			}

			this.currentModal.fadeIn( 200 );

			/* Populate sample selector for post/taxonomy */
			if ( 'post' === entityType || 'taxonomy' === entityType ) {
				this.loadSampleOptions( entityType, entityKey );
			}

			/* Auto-load fields for CF7 */
			if ( 'cf7' === entityType ) {
				this.loadCF7Fields( entityKey );
			} else if ( 'user' !== entityType ) {
				/* Load default columns without sample */
				this.loadFields( entityType, 0 );
			} else {
				/* For users, render saved fields to preserve meta field config */
				this.renderSavedFieldsOrPlaceholder();
			}
		},

		/* Modal events */
		bindModalEvents: function() {
			var self = this;

			/* Close modal */
			$( document ).on( 'click', '.flowbridge-modal-close, .flowbridge-modal-cancel, .flowbridge-modal-overlay', function() {
				$( this ).closest( '.flowbridge-modal' ).fadeOut( 200 );
				self.currentModal = null;
			});

			/* Load fields button */
			$( document ).on( 'click', '.flowbridge-load-fields-btn', function() {
				if ( ! self.currentModal ) return;

				var $selector = self.currentModal.find( '.flowbridge-sample-selector' );
				var sampleId = $selector.val();
				self.loadFields( self.currentEntityType, sampleId || 0 );
			});

			/* Load CF7 fields button */
			$( document ).on( 'click', '.flowbridge-load-cf7-fields-btn', function() {
				if ( ! self.currentModal ) return;
				self.loadCF7Fields( self.currentEntityKey );
			});

			/* Check all */
			$( document ).on( 'change', '.flowbridge-check-all', function() {
				var checked = $( this ).is( ':checked' );
				$( this ).closest( 'table' ).find( 'tbody input[type="checkbox"]' ).prop( 'checked', checked );
			});

			/* Save configuration */
			$( document ).on( 'click', '.flowbridge-save-config-btn', function() {
				self.saveConfig();
			});

			/* Preview output */
			$( document ).on( 'click', '.flowbridge-preview-output-btn', function() {
				self.previewOutput();
			});

			/* Send test event */
			$( document ).on( 'click', '.flowbridge-send-test-event-btn', function() {
				self.sendTestEvent();
			});

			/* Close on Escape */
			$( document ).on( 'keydown', function( e ) {
				if ( 27 === e.keyCode && self.currentModal ) {
					self.currentModal.fadeOut( 200 );
					self.currentModal = null;
				}
			});
		},

		/* Populate sample selector via AJAX */
		loadSampleOptions: function( entityType, entityKey ) {
			var $select = this.currentModal.find( '.flowbridge-sample-selector' );
			var action = 'post' === entityType
				? 'flowbridge_n8n_load_sample_posts'
				: 'flowbridge_n8n_load_sample_terms';
			var paramKey = 'post' === entityType ? 'post_type' : 'taxonomy';

			/* Clear existing options, keep the default */
			$select.find( 'option:not(:first)' ).remove();
			$select.prop( 'disabled', true );

			var postData = {
				action: action,
				nonce: flowbridgeAdmin.nonce
			};
			postData[ paramKey ] = entityKey;

			$.post( flowbridgeAdmin.ajaxUrl, postData, function( response ) {
				$select.prop( 'disabled', false );
				if ( response.success && response.data.items ) {
					$.each( response.data.items, function( i, item ) {
						$select.append(
							$( '<option>' ).val( item.id ).text( item.title )
						);
					});
				}
			}).fail( function() {
				$select.prop( 'disabled', false );
			});
		},

		/* Render saved fields from config when no sample is loaded (preserves meta fields) */
		renderSavedFieldsOrPlaceholder: function() {
			if ( ! this.currentModal ) return;

			var savedConfig = this.getSavedConfig( this.currentEntityType, this.currentEntityKey );
			if ( savedConfig && savedConfig.fields && savedConfig.fields.length ) {
				var fields = [];
				$.each( savedConfig.fields, function( i, sf ) {
					fields.push({
						source: sf.source,
						label: sf.label || sf.source,
						is_meta: !! sf.is_meta,
						sample: ''
					});
				});
				this.renderFieldsTable( fields );
			} else {
				/* No saved config — show placeholder message */
				var $tbody = this.currentModal.find( '.flowbridge-fields-table tbody' );
				$tbody.html(
					'<tr class="flowbridge-fields-empty"><td colspan="6">' +
					( flowbridgeAdmin.i18n.selectSampleFirst || 'Select a sample and click "Load Fields" to see available fields.' ) +
					'</td></tr>'
				);
			}
		},

		/* Load fields via AJAX */
		loadFields: function( entityType, sampleId ) {
			var self = this;
			if ( ! this.currentModal ) return;

			/* Abort any pending fields request to prevent race conditions */
			if ( this.pendingFieldsRequest ) {
				this.pendingFieldsRequest.abort();
				this.pendingFieldsRequest = null;
			}

			var actionMap = {
				post: 'flowbridge_n8n_load_post_fields',
				taxonomy: 'flowbridge_n8n_load_term_fields',
				user: 'flowbridge_n8n_load_user_fields'
			};

			var action = actionMap[ entityType ];
			if ( ! action ) return;

			var dataKey = 'post_id';
			if ( 'taxonomy' === entityType ) dataKey = 'term_id';
			if ( 'user' === entityType ) dataKey = 'user_id';

			var postData = {
				action: action,
				nonce: flowbridgeAdmin.nonce
			};
			postData[ dataKey ] = sampleId;

			if ( 'post' === entityType ) {
				postData.post_type = self.currentEntityKey;
			}

			var $tbody = this.currentModal.find( '.flowbridge-fields-table tbody' );
			$tbody.html( '<tr><td colspan="6"><span class="flowbridge-spinner"></span> ' + flowbridgeAdmin.i18n.loading + '</td></tr>' );

			this.pendingFieldsRequest = $.post( flowbridgeAdmin.ajaxUrl, postData, function( response ) {
				self.pendingFieldsRequest = null;
				if ( response.success && response.data.fields ) {
					self.renderFieldsTable( response.data.fields );
				} else {
					$tbody.html( '<tr class="flowbridge-fields-empty"><td colspan="6">' + flowbridgeAdmin.i18n.noFields + '</td></tr>' );
				}
			}).fail( function( jqXHR, textStatus ) {
				self.pendingFieldsRequest = null;
				if ( 'abort' === textStatus ) return;
				$tbody.html( '<tr class="flowbridge-fields-empty"><td colspan="6">' + flowbridgeAdmin.i18n.error + '</td></tr>' );
			});
		},

		/* Load CF7 fields */
		loadCF7Fields: function( formId ) {
			var self = this;
			if ( ! this.currentModal ) return;

			var $tbody = this.currentModal.find( '.flowbridge-fields-table tbody' );
			$tbody.html( '<tr><td colspan="6"><span class="flowbridge-spinner"></span> ' + flowbridgeAdmin.i18n.loading + '</td></tr>' );

			$.post( flowbridgeAdmin.ajaxUrl, {
				action: 'flowbridge_n8n_load_cf7_fields',
				nonce: flowbridgeAdmin.nonce,
				form_id: formId
			}, function( response ) {
				if ( response.success && response.data.fields ) {
					self.renderFieldsTable( response.data.fields );
				} else {
					var msg = ( response.data && response.data.message ) ? response.data.message : flowbridgeAdmin.i18n.noFields;
					$tbody.html( '<tr class="flowbridge-fields-empty"><td colspan="6">' + msg + '</td></tr>' );
				}
			}).fail( function() {
				$tbody.html( '<tr class="flowbridge-fields-empty"><td colspan="6">' + flowbridgeAdmin.i18n.error + '</td></tr>' );
			});
		},

		/* Render fields into the table */
		renderFieldsTable: function( fields ) {
			if ( ! this.currentModal ) return;

			var self = this;
			var $tbody = this.currentModal.find( '.flowbridge-fields-table tbody' );
			$tbody.empty();

			if ( ! fields.length ) {
				$tbody.html( '<tr class="flowbridge-fields-empty"><td colspan="6">' + flowbridgeAdmin.i18n.noFields + '</td></tr>' );
				return;
			}

			var typeOptions = '<option value="string">String</option>' +
				'<option value="int">Integer</option>' +
				'<option value="float">Float</option>' +
				'<option value="bool">Boolean</option>' +
				'<option value="json">JSON</option>';

			$.each( fields, function( i, field ) {
				var sendAs = field.source.replace( /^meta:/, '' ).replace( /[^a-z0-9_]/gi, '_' );
				var metaBadge = field.is_meta ? '<span class="flowbridge-field-meta-badge">META</span>' : '';
				var sampleVal = field.sample ? self.escHtml( field.sample ) : '<span class="flowbridge-sample-empty">&mdash;</span>';

				var $row = $( '<tr>' );
				$row.append( '<td><input type="checkbox" class="flowbridge-field-enabled" checked /></td>' );
				$row.append( '<td><code>' + self.escHtml( field.source ) + '</code></td>' );
				$row.append( '<td class="flowbridge-sample-value">' + sampleVal + '</td>' );
				$row.append( '<td><input type="text" class="flowbridge-field-send-as" value="' + self.escAttr( sendAs ) + '" /></td>' );
				$row.append( '<td><select class="flowbridge-field-type">' + typeOptions + '</select></td>' );
				$row.append( '<td>' + metaBadge + '</td>' );

				$row.data( 'source', field.source );
				$row.data( 'label', field.label );
				$row.data( 'is-meta', field.is_meta );

				$tbody.append( $row );
			});

			/* Apply saved field overrides */
			var savedConfig = this.getSavedConfig( this.currentEntityType, this.currentEntityKey );
			if ( savedConfig && savedConfig.fields && savedConfig.fields.length ) {
				var savedBySource = {};
				$.each( savedConfig.fields, function( i, sf ) {
					savedBySource[ sf.source ] = sf;
				});

				$tbody.find( 'tr' ).each( function() {
					var $row = $( this );
					var source = $row.data( 'source' );
					if ( ! source ) return;

					if ( savedBySource[ source ] ) {
						var sf = savedBySource[ source ];
						$row.find( '.flowbridge-field-enabled' ).prop( 'checked', !! sf.enabled );
						if ( sf.send_as ) {
							$row.find( '.flowbridge-field-send-as' ).val( sf.send_as );
						}
						if ( sf.type ) {
							$row.find( '.flowbridge-field-type' ).val( sf.type );
						}
					} else {
						// Field not in saved config — uncheck so user knows it needs saving.
						$row.find( '.flowbridge-field-enabled' ).prop( 'checked', false );
					}
				});
			}
		},

		/* Gather the current modal state (events + fields) into a config object */
		gatherModalConfig: function() {
			if ( ! this.currentModal ) return null;

			var $modal = this.currentModal;

			var events = [];
			$modal.find( 'input[name="events[]"]:checked' ).each( function() {
				events.push( $( this ).val() );
			});

			var fields = [];
			$modal.find( '.flowbridge-fields-table tbody tr' ).each( function() {
				var $row = $( this );
				if ( $row.hasClass( 'flowbridge-fields-empty' ) ) return;

				fields.push({
					source: $row.data( 'source' ),
					label: $row.data( 'label' ),
					send_as: $row.find( '.flowbridge-field-send-as' ).val(),
					type: $row.find( '.flowbridge-field-type' ).val(),
					enabled: $row.find( '.flowbridge-field-enabled' ).is( ':checked' ),
					is_meta: $row.data( 'is-meta' )
				});
			});

			return {
				enabled: true,
				events: events,
				fields: fields
			};
		},

		/* Save the current modal config */
		saveConfig: function() {
			if ( ! this.currentModal ) return;

			var $modal = this.currentModal;
			var $status = $modal.find( '.flowbridge-modal-status' );
			var $btn = $modal.find( '.flowbridge-save-config-btn' );

			var config = this.gatherModalConfig();
			if ( ! config ) return;

			$status.text( '' ).removeClass( 'success error' );
			this.setButtonLoading( $btn, true, flowbridgeAdmin.i18n.saving );

			var self = this;

			$.post( flowbridgeAdmin.ajaxUrl, {
				action: 'flowbridge_n8n_save_entity_config',
				nonce: flowbridgeAdmin.nonce,
				entity_type: this.currentEntityType,
				entity_key: this.currentEntityKey,
				config: JSON.stringify( config )
			}, function( response ) {
				if ( response.success ) {
					/* Update local config cache */
					if ( 'user' === self.currentEntityType ) {
						flowbridgeAdmin.entityConfigs.user = config;
					} else {
						if ( ! flowbridgeAdmin.entityConfigs[ self.currentEntityType ] ) {
							flowbridgeAdmin.entityConfigs[ self.currentEntityType ] = {};
						}
						flowbridgeAdmin.entityConfigs[ self.currentEntityType ][ self.currentEntityKey ] = config;
					}

					$status.text( flowbridgeAdmin.i18n.saved ).addClass( 'success' );
					setTimeout( function() {
						$modal.fadeOut( 200 );
						self.currentModal = null;
						location.reload();
					}, 800 );
				} else {
					self.setButtonLoading( $btn, false );
					var msg = ( response.data && response.data.message ) ? response.data.message : flowbridgeAdmin.i18n.error;
					$status.text( msg ).addClass( 'error' );
				}
			}).fail( function() {
				self.setButtonLoading( $btn, false );
				$status.text( flowbridgeAdmin.i18n.error ).addClass( 'error' );
			});
		},

		/* Send test event from modal */
		sendTestEvent: function() {
			if ( ! this.currentModal ) return;

			var $status = this.currentModal.find( '.flowbridge-modal-status' );
			var $btn = this.currentModal.find( '.flowbridge-send-test-event-btn' );
			var sampleId = 0;

			if ( 'cf7' !== this.currentEntityType ) {
				var $selector = this.currentModal.find( '.flowbridge-sample-selector' );
				sampleId = $selector.val();
				if ( ! sampleId ) {
					$status.text( flowbridgeAdmin.i18n.selectSampleFirst || 'Select a sample first.' ).removeClass( 'success' ).addClass( 'error' );
					return;
				}
			}

			/* Pre-flight webhook URL check */
			var useLive = false;

			if ( flowbridgeAdmin.hasTestWebhookUrl ) {
				if ( ! confirm( flowbridgeAdmin.i18n.confirmTestEvent ) ) {
					return;
				}
			} else if ( flowbridgeAdmin.hasWebhookUrl ) {
				if ( ! confirm( flowbridgeAdmin.i18n.confirmLiveFallback ) ) {
					return;
				}
				useLive = true;
			} else {
				$status.text( flowbridgeAdmin.i18n.noWebhookUrlConfigured )
					.removeClass( 'success' ).addClass( 'error' );
				return;
			}

			var self = this;
			$status.text( '' ).removeClass( 'success error' );
			self.setButtonLoading( $btn, true, flowbridgeAdmin.i18n.sendingTestEvent || 'Sending...' );

			$.post( flowbridgeAdmin.ajaxUrl, {
				action: 'flowbridge_n8n_send_test_event',
				nonce: flowbridgeAdmin.nonce,
				entity_type: this.currentEntityType,
				entity_key: this.currentEntityKey,
				sample_id: sampleId,
				use_live: useLive ? 1 : 0
			}, function( response ) {
				self.setButtonLoading( $btn, false );
				var msg = ( response.data && response.data.message ) ? response.data.message : '';
				if ( response.success ) {
					$status.text( msg ).removeClass( 'error' ).addClass( 'success' );
				} else {
					$status.text( msg || flowbridgeAdmin.i18n.error ).removeClass( 'success' ).addClass( 'error' );
				}
			}).fail( function() {
				self.setButtonLoading( $btn, false );
				$status.text( flowbridgeAdmin.i18n.error ).removeClass( 'success' ).addClass( 'error' );
			});
		},

		/* Preview JSON output from current modal state */
		previewOutput: function() {
			if ( ! this.currentModal ) return;

			var $status = this.currentModal.find( '.flowbridge-modal-status' );
			var $btn = this.currentModal.find( '.flowbridge-preview-output-btn' );
			var $section = this.currentModal.find( '.flowbridge-preview-section' );
			var $json = this.currentModal.find( '.flowbridge-preview-json' );
			var sampleId = 0;

			if ( 'cf7' !== this.currentEntityType ) {
				var $selector = this.currentModal.find( '.flowbridge-sample-selector' );
				sampleId = $selector.val();
				if ( ! sampleId ) {
					$status.text( flowbridgeAdmin.i18n.selectSampleForPreview ).removeClass( 'success' ).addClass( 'error' );
					return;
				}
			}

			var config = this.gatherModalConfig();
			if ( ! config ) return;

			var self = this;
			$status.text( '' ).removeClass( 'success error' );
			self.setButtonLoading( $btn, true, flowbridgeAdmin.i18n.previewLoading );

			$.post( flowbridgeAdmin.ajaxUrl, {
				action: 'flowbridge_n8n_preview_payload',
				nonce: flowbridgeAdmin.nonce,
				entity_type: this.currentEntityType,
				entity_key: this.currentEntityKey,
				sample_id: sampleId,
				config: JSON.stringify( config )
			}, function( response ) {
				self.setButtonLoading( $btn, false );
				$status.text( '' ).removeClass( 'success error' );

				if ( response.success && response.data.payload ) {
					$json.text( JSON.stringify( response.data.payload, null, 2 ) );
					$section.slideDown( 200, function() {
						$section[0].scrollIntoView( { behavior: 'smooth', block: 'end' } );
					});
				} else {
					var msg = ( response.data && response.data.message ) ? response.data.message : flowbridgeAdmin.i18n.previewError;
					$status.text( msg ).addClass( 'error' );
				}
			}).fail( function() {
				self.setButtonLoading( $btn, false );
				$status.text( flowbridgeAdmin.i18n.previewError ).removeClass( 'success' ).addClass( 'error' );
			});
		},

		/* Toggle WordPress built-in post types */
		bindWpDefaultToggle: function() {
			$( document ).on( 'click', '.flowbridge-toggle-wp-defaults-btn', function() {
				var $btn = $( this );
				var $wrapper = $btn.next( '.flowbridge-wp-default-types' );
				$wrapper.slideToggle( 200 );
				if ( $wrapper.is( ':visible' ) ) {
					$btn.text( flowbridgeAdmin.i18n.hideWpDefaults || 'Hide WordPress Built-in Types' );
				} else {
					$btn.text( flowbridgeAdmin.i18n.showWpDefaults || 'Show WordPress Built-in Types' );
				}
			});
		},

		/* Test webhook button */
		bindTestWebhook: function() {
			$( document ).on( 'click', '#flowbridge-test-webhook', function() {
				var $btn = $( this );
				var $result = $( '#flowbridge-test-result' );

				$btn.prop( 'disabled', true ).text( flowbridgeAdmin.i18n.testSending );
				$result.hide().removeClass( 'success error' );

				$.post( flowbridgeAdmin.ajaxUrl, {
					action: 'flowbridge_n8n_test_webhook',
					nonce: flowbridgeAdmin.nonce
				}, function( response ) {
					$btn.prop( 'disabled', false ).text( 'Test Webhook' );
					var msg = ( response.data && response.data.message ) ? response.data.message : '';

					if ( response.success ) {
						$result.text( msg || flowbridgeAdmin.i18n.testSuccess ).addClass( 'success' ).show();
					} else {
						$result.text( msg || flowbridgeAdmin.i18n.testError ).addClass( 'error' ).show();
					}
				}).fail( function() {
					$btn.prop( 'disabled', false ).text( 'Test Webhook' );
					$result.text( flowbridgeAdmin.i18n.testError ).addClass( 'error' ).show();
				});
			});
		},

		/* Get saved config for an entity */
		getSavedConfig: function( entityType, entityKey ) {
			if ( ! flowbridgeAdmin.entityConfigs ) {
				return null;
			}

			if ( 'user' === entityType ) {
				return flowbridgeAdmin.entityConfigs.user || null;
			}

			var configs = flowbridgeAdmin.entityConfigs[ entityType ];
			if ( configs && configs[ entityKey ] ) {
				return configs[ entityKey ];
			}

			return null;
		},

		/* Utility: set button loading state */
		setButtonLoading: function( $btn, loading, loadingText ) {
			if ( loading ) {
				$btn.data( 'original-text', $btn.html() );
				$btn.html( '<span class="flowbridge-btn-spinner"></span> ' + loadingText );
				$btn.prop( 'disabled', true ).addClass( 'flowbridge-btn-loading' );
			} else {
				$btn.html( $btn.data( 'original-text' ) );
				$btn.prop( 'disabled', false ).removeClass( 'flowbridge-btn-loading' );
			}
		},

		/* Utility: escape HTML */
		escHtml: function( str ) {
			var div = document.createElement( 'div' );
			div.appendChild( document.createTextNode( str ) );
			return div.innerHTML;
		},

		/* Utility: escape attribute */
		escAttr: function( str ) {
			return String( str )
				.replace( /&/g, '&amp;' )
				.replace( /"/g, '&quot;' )
				.replace( /'/g, '&#039;' )
				.replace( /</g, '&lt;' )
				.replace( />/g, '&gt;' );
		},

		/* Logs page: payload viewer modal + clear logs */
		bindLogs: function() {
			var self = this;

			/* View payload button */
			$( document ).on( 'click', '.flowbridge-view-payload-btn', function() {
				var $btn = $( this );
				var payload = $btn.data( 'payload' );
				var eventName = $btn.data( 'event' );
				var responseMsg = $btn.data( 'response' );
				var $modal = $( '#flowbridge-modal-payload' );

				$modal.find( '.flowbridge-modal-entity-label' ).text( eventName );

				/* Pretty-print JSON */
				var formatted = '';
				try {
					formatted = JSON.stringify( JSON.parse( payload ), null, 2 );
				} catch ( e ) {
					formatted = String( payload );
				}
				$modal.find( '.flowbridge-payload-json' ).text( formatted );

				/* Response message */
				if ( responseMsg ) {
					$modal.find( '.flowbridge-response-section' ).show();
					$modal.find( '.flowbridge-response-message' ).text( responseMsg );
				} else {
					$modal.find( '.flowbridge-response-section' ).hide();
				}

				$modal.fadeIn( 200 );
			});

			/* Clear all logs */
			$( document ).on( 'click', '#flowbridge-clear-logs-btn', function() {
				if ( ! confirm( flowbridgeAdmin.i18n.confirmClearLogs || 'Are you sure you want to delete all logs? This cannot be undone.' ) ) {
					return;
				}

				var $btn = $( this );
				$btn.prop( 'disabled', true );

				$.post( flowbridgeAdmin.ajaxUrl, {
					action: 'flowbridge_n8n_clear_logs',
					nonce: flowbridgeAdmin.nonce
				}, function( response ) {
					$btn.prop( 'disabled', false );
					if ( response.success ) {
						location.reload();
					}
				}).fail( function() {
					$btn.prop( 'disabled', false );
				});
			});
		}
	};

	$( document ).ready( function() {
		FlowBridge.init();
	});

})( jQuery );
