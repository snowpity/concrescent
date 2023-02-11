(function($,window,document,cmui,formdef){
	var doAjax = function(message, request, done) {
		cmui.showButterbar(message);
		$.post((formdef['ajax-url'] || ''), request, function(response) {
			if (!response['ok']) {
				cmui.showButterbarPersistent('An error occurred. Please try again.');
			} else {
				done(response);
				cmui.hideButterbar();
			}
		}, 'json');
	};
	var setClass = function(e, c, b) {
		if (b) e.addClass(c);
		else e.removeClass(c);
	};
	var typeTakesTitle = function(type) {
		return (type != 'hr');
	};
	var typeTakesText = function(type) {
		return (type != 'hr');
	};
	var typeTakesValues = function(type) {
		return (type == 'radio' || type == 'checkbox' || type == 'select');
	};
	var pushToEditor = function(editor, question) {
		/* Type, Title, Text, Values, Active, Listed */
		var type = (question['type'] || 'text');
		setClass(editor.filter('.ear-title'), 'hidden', !typeTakesTitle(type));
		setClass(editor.filter('.ear-text'), 'hidden', !typeTakesText(type));
		setClass(editor.filter('.ear-values'), 'hidden', !typeTakesValues(type));
		editor.find('.ea-type').val(type);
		editor.find('.ea-title').val(question['title'] || '');
		editor.find('.ea-text').val(question['text'] || '');
		editor.find('.ea-values').val((question['values'] || []).join('\n'));
		editor.find('.ea-active').prop('checked', !!question['active']);
		editor.find('.ea-listed').prop('checked', !!question['listed']);
		editor.find('.ea-exposed').prop('checked', !!question['exposed']);
		/* Visible */
		if (question['visible'] && question['visible'].indexOf('*') >= 0) {
			editor.find('.ea-visible').prop('checked', true);
			editor.filter('.ear-visible-advanced').addClass('hidden');
			editor.filter('.ear-visible-advanced').find('input').prop('checked', true);
		} else {
			editor.find('.ea-visible').prop('checked', false);
			editor.filter('.ear-visible-advanced').addClass('hidden');
			editor.filter('.ear-visible-advanced').find('input').prop('checked', false);
			for (var i = 0, n = formdef['subcontext'].length; i < n; ++i) {
				var id = String(formdef['subcontext'][i]['id']);
				var checked = (question['visible'] && question['visible'].indexOf(id) >= 0);
				editor.find('.ea-visible-' + id).prop('checked', checked);
				if (checked) editor.filter('.ear-visible-advanced').removeClass('hidden');
			}
		}
		/* Required */
		if (question['required'] && question['required'].indexOf('*') >= 0) {
			editor.find('.ea-required').prop('checked', true);
			editor.filter('.ear-required-advanced').addClass('hidden');
			editor.filter('.ear-required-advanced').find('input').prop('checked', true);
		} else {
			editor.find('.ea-required').prop('checked', false);
			editor.filter('.ear-required-advanced').addClass('hidden');
			editor.filter('.ear-required-advanced').find('input').prop('checked', false);
			for (var i = 0, n = formdef['subcontext'].length; i < n; ++i) {
				var id = String(formdef['subcontext'][i]['id']);
				var checked = (question['required'] && question['required'].indexOf(id) >= 0);
				editor.find('.ea-required-' + id).prop('checked', checked);
				if (checked) editor.filter('.ear-required-advanced').removeClass('hidden');
			}
		}
	};
	var pullFromEditor = function(editor, question) {
		/* Type, Title, Text, Values, Active, Listed */
		if (!question) question = {};
		var type = (editor.find('.ea-type').val() || 'text');
		var title = (typeTakesTitle(type) ? (editor.find('.ea-title').val() || '') : '');
		var text = (typeTakesText(type) ? (editor.find('.ea-text').val() || '') : '');
		var values = (typeTakesValues(type) ? (editor.find('.ea-values').val() || '') : '');
		values = values.replace(/\r\n/g, '\n');
		values = values.replace(/\r/g, '\n');
		values = values.replace(/\n+/g, '\n').trim();
		values = values ? values.split('\n') : [];
		question['type'] = type;
		question['title'] = title;
		question['text'] = text;
		question['values'] = values;
		question['active'] = editor.find('.ea-active').is(':checked');
		question['listed'] = editor.find('.ea-listed').is(':checked');
		question['exposed'] = editor.find('.ea-exposed').is(':checked');
		/* Visible */
		if (editor.find('.ea-visible').is(':checked')) {
			question['visible'] = ['*'];
		} else {
			question['visible'] = [];
			for (var i = 0, n = formdef['subcontext'].length; i < n; ++i) {
				var id = formdef['subcontext'][i]['id'];
				var checked = editor.find('.ea-visible-' + id).is(':checked');
				if (checked) question['visible'].push(id);
			}
		}
		/* Required */
		if (editor.find('.ea-required').is(':checked')) {
			question['required'] = ['*'];
		} else {
			question['required'] = [];
			for (var i = 0, n = formdef['subcontext'].length; i < n; ++i) {
				var id = formdef['subcontext'][i]['id'];
				var checked = editor.find('.ea-required-' + id).is(':checked');
				if (checked) question['required'].push(id);
			}
		}
		return question;
	};
	var prepEditor = function(editor, onChange) {
		/* Type */
		var typeField = editor.find('.ea-type');
		var typeOldVal = typeField.val();
		var typeChanged = function() {
			var typeNewVal = typeField.val();
			if (typeNewVal != typeOldVal) {
				typeOldVal = typeNewVal;
				setClass(editor.filter('.ear-title'), 'hidden', !typeTakesTitle(typeNewVal));
				setClass(editor.filter('.ear-text'), 'hidden', !typeTakesText(typeNewVal));
				setClass(editor.filter('.ear-values'), 'hidden', !typeTakesValues(typeNewVal));
				if (onChange) onChange(editor, 'type', typeNewVal);
			}
		};
		typeField.bind('change', typeChanged);
		typeField.bind('keydown', typeChanged);
		typeField.bind('keyup', typeChanged);
		typeField.bind('mousedown', typeChanged);
		typeField.bind('mouseup', typeChanged);
		/* Title */
		var titleField = editor.find('.ea-title');
		var titleFieldOldVal = titleField.val();
		var titleFieldChanged = function() {
			var titleFieldNewVal = titleField.val();
			if (titleFieldNewVal != titleFieldOldVal) {
				titleFieldOldVal = titleFieldNewVal;
				if (onChange) onChange(editor, 'title', titleFieldNewVal);
			}
		};
		titleField.bind('change', titleFieldChanged);
		titleField.bind('keydown', titleFieldChanged);
		titleField.bind('keyup', titleFieldChanged);
		/* Text */
		var textField = editor.find('.ea-text');
		var textFieldOldVal = textField.val();
		var textFieldChanged = function() {
			var textFieldNewVal = textField.val();
			if (textFieldNewVal != textFieldOldVal) {
				textFieldOldVal = textFieldNewVal;
				if (onChange) onChange(editor, 'text', textFieldNewVal);
			}
		};
		textField.bind('change', textFieldChanged);
		textField.bind('keydown', textFieldChanged);
		textField.bind('keyup', textFieldChanged);
		/* Values */
		var valuesField = editor.find('.ea-values');
		var valuesFieldOldVal = valuesField.val();
		var valuesFieldChanged = function() {
			var valuesFieldNewVal = valuesField.val();
			if (valuesFieldNewVal != valuesFieldOldVal) {
				valuesFieldOldVal = valuesFieldNewVal;
				if (onChange) onChange(editor, 'values', valuesFieldNewVal);
			}
		};
		valuesField.bind('change', valuesFieldChanged);
		valuesField.bind('keydown', valuesFieldChanged);
		valuesField.bind('keyup', valuesFieldChanged);
		/* Active & Listed */
		editor.find('.ea-active').bind('click', function() {
			if (onChange) onChange(editor, 'active', $(this).is(':checked'));
		});
		editor.find('.ea-listed').bind('click', function() {
			if (onChange) onChange(editor, 'listed', $(this).is(':checked'));
		});
		editor.find('.ea-exposed').bind('click', function() {
			if (onChange) onChange(editor, 'listed', $(this).is(':checked'));
		});
		/* Visible */
		editor.find('.ea-visible').bind('click', function() {
			var checked = editor.find('.ea-visible').is(':checked');
			editor.filter('.ear-visible-advanced').find('input').prop('checked', checked);
			if (onChange) onChange(editor, 'visible', checked);
		});
		editor.find('.ea-visible-advanced').bind('click', function(event) {
			var checked = editor.filter('.ear-visible-advanced').find('input').is(':checked');
			var notChecked = editor.filter('.ear-visible-advanced').find('input').is(':not(:checked)');
			if (!(checked && notChecked)) editor.filter('.ear-visible-advanced').toggleClass('hidden');
			event.preventDefault();
		});
		editor.filter('.ear-visible-advanced').find('input').bind('click', function() {
			var checked = !editor.filter('.ear-visible-advanced').find('input').is(':not(:checked)');
			editor.find('.ea-visible').prop('checked', checked);
			if (onChange) onChange(editor, 'visible', checked);
		});
		/* Required */
		editor.find('.ea-required').bind('click', function() {
			var checked = editor.find('.ea-required').is(':checked');
			editor.filter('.ear-required-advanced').find('input').prop('checked', checked);
			if (onChange) onChange(editor, 'required', checked);
		});
		editor.find('.ea-required-advanced').bind('click', function(event) {
			var checked = editor.filter('.ear-required-advanced').find('input').is(':checked');
			var notChecked = editor.filter('.ear-required-advanced').find('input').is(':not(:checked)');
			if (!(checked && notChecked)) editor.filter('.ear-required-advanced').toggleClass('hidden');
			event.preventDefault();
		});
		editor.filter('.ear-required-advanced').find('input').bind('click', function() {
			var checked = !editor.filter('.ear-required-advanced').find('input').is(':not(:checked)');
			editor.find('.ea-required').prop('checked', checked);
			if (onChange) onChange(editor, 'required', checked);
		});
	};
	var renderQuestion = function(question, done) {
		cmui.showButterbar('Loading...');
		$.post((formdef['ajax-url'] || ''), {
			'cm-form-action': 'render-dynamic-row',
			'cm-form-question': JSON.stringify(question)
		}, function(response) {
			if (!response) {
				cmui.showButterbarPersistent('An error occurred. Please try again.');
			} else {
				done(response);
				cmui.hideButterbar();
			}
		});
	};
	var saveQuestionOrder = function(tbody) {
		var ids = [];
		tbody.find('.cm-form-editor-dynamic-row').each(function() {
			var id = $(this).attr('id').substring(11);
			if (id.substring(0, 4) != 'NEW-') ids.push(id);
		});
		doAjax('Saving...', {
			'cm-form-action': 'set-question-order',
			'cm-form-question-order': JSON.stringify(ids)
		}, function() {});
	};

	$(document).ready(function() {
		/* Custom Text Sections */
		$('.cm-form-editor-custom-text-section').each(function() {
			var self = $(this);
			var id = self.attr('id').substring(13);
			var defaultHtml = self.find('.view-row .view-area').html();
			var currentText, currentHtml;
			var loadContent = function() {
				doAjax('Loading...', {
					'cm-form-action': 'load-custom-text',
					'cm-form-ct-name': id
				}, function(response) {
					var text = response['text'];
					var html = cmui.safeHtmlString(text);
					self.find('.view-row .view-area').html(html || defaultHtml);
					self.find('.edit-row textarea').val(text);
					currentText = text;
					currentHtml = html;
				});
			};
			var editContent = function() {
				self.addClass('editing');
				self.find('.edit-row').removeClass('hidden');
				var textarea = self.find('.edit-row textarea');
				if (!textarea.val()) textarea.val(defaultHtml);
				textarea.focus();
			};
			var previewContent = function() {
				var text = self.find('.edit-row textarea').val();
				var html = cmui.safeHtmlString(text);
				self.find('.view-row .view-area').html(html || defaultHtml);
			};
			var revertContent = function() {
				self.removeClass('editing');
				self.find('.edit-row').addClass('hidden');
				self.find('.view-row .view-area').html(currentHtml || defaultHtml);
				self.find('.edit-row textarea').val(currentText);
			};
			var saveContent = function() {
				var textarea = self.find('.edit-row textarea');
				var text = textarea.val();
				if (text == defaultHtml) textarea.val((text = ''));
				var html = cmui.safeHtmlString(text);
				self.find('.view-row .view-area').html(html || defaultHtml);
				doAjax('Saving...', {
					'cm-form-action': 'save-custom-text',
					'cm-form-ct-name': id,
					'cm-form-ct-text': text
				}, function(response) {
					self.removeClass('editing');
					self.find('.edit-row').addClass('hidden');
					currentText = text;
					currentHtml = html;
				});
			};
			/* Bind Events */
			self.find('.view-row').bind('click', editContent);
			var textArea = self.find('.edit-row textarea');
			var textAreaOldVal = textArea.val();
			var textAreaChanged = function() {
				var textAreaNewVal = textArea.val();
				if (textAreaNewVal != textAreaOldVal) {
					textAreaOldVal = textAreaNewVal;
					previewContent();
				}
			};
			textArea.bind('change', textAreaChanged);
			textArea.bind('keydown', textAreaChanged);
			textArea.bind('keyup', textAreaChanged);
			self.find('.cancel-edit-button').bind('click', revertContent);
			self.find('.confirm-edit-button').bind('click', saveContent);
			loadContent();
		});

		/* Dynamic Form Section */
		var prepDynamicRow = function(tbody, tr, question, isNew) {
			tr.addClass('cm-form-editor-dynamic-row');
			setClass(tr, 'inactive', !question['active']);
			tr.attr('id', 'questionid-' + question['question-id']);
			tr.attr('title', 'Click to edit question.');
			tbody.append(tr);
			tr.bind('click', function() {
				if (tr.hasClass('editing')) return;
				var editor = $('.cm-form-editor-dynamic-section-editor tr').clone();
				editor.addClass('editorid-' + question['question-id']);
				tr.after(editor);
				tr.addClass('editing');
				var oldQuic = ((question['title']?1:2) | (question['text']?4:8));
				pushToEditor(editor, question);
				prepEditor(editor, function(e, what, value) {
					switch (what) {
						case 'type':
						case 'values':
							var newQuestion = pullFromEditor(editor, {});
							renderQuestion(newQuestion, function(html) {
								tr.html($(html).html());
							});
							break;
						case 'title':
						case 'text':
							var newQuic = ((editor.find('.ea-title').val()?1:2) | (editor.find('.ea-text').val()?4:8));
							if (newQuic != oldQuic) {
								var newQuestion = pullFromEditor(editor, {});
								renderQuestion(newQuestion, function(html) {
									tr.html($(html).html());
									oldQuic = newQuic;
								});
							} else {
								var html = cmui.safeHtmlString(value, 'cm-question-' + what);
								tr.find('.cm-question-' + what).not(':eq(0)').remove();
								tr.find('.cm-question-' + what).replaceWith($(html));
								tr.find('th > label').text(value);
							}
							break;
						case 'active':
							setClass(tr, 'inactive', !value);
							break;
					}
				});
				editor.find('.confirm-edit-button').bind('click', function() {
					var questionId = {'question-id': question['question-id']};
					var newQuestion = pullFromEditor(editor, questionId);
					doAjax('Saving...', {
						'cm-form-action': (isNew ? 'create-question' : 'update-question'),
						'cm-form-question': JSON.stringify(newQuestion)
					}, function(response) {
						question = response['question']; isNew = false;
						setClass(tr, 'inactive', !question['active']);
						tr.attr('id', 'questionid-' + question['question-id']);
						tr.html($(response['html']).html());
						editor.remove();
						tr.removeClass('editing');
						saveQuestionOrder(tbody);
					});
				});
				editor.find('.cancel-edit-button').bind('click', function() {
					if (isNew) {
						tr.remove();
						editor.remove();
					} else {
						renderQuestion(question, function(html) {
							setClass(tr, 'inactive', !question['active']);
							tr.html($(html).html());
							editor.remove();
							tr.removeClass('editing');
						});
					}
				});
				editor.find('.up-button').bind('click', function() {
					var prevTr = tr.prevAll('.cm-form-editor-dynamic-row:eq(0)');
					if (prevTr.length) {
						prevTr.before(tr);
						prevTr.before(editor);
						saveQuestionOrder(tbody);
					}
				});
				editor.find('.down-button').bind('click', function() {
					var nextTr = tr.nextAll('.cm-form-editor-dynamic-row:eq(0)');
					if (nextTr.length) {
						var nextNextTr = nextTr.nextAll('.cm-form-editor-dynamic-row:eq(0)');
						if (nextNextTr.length) {
							nextNextTr.before(tr);
							nextNextTr.before(editor);
						} else {
							tbody.append(tr);
							tbody.append(editor);
						}
						saveQuestionOrder(tbody);
					}
				});
				editor.find('.delete-button').bind('click', function() {
					var newQuestion = pullFromEditor(editor, {});
					var text = newQuestion['text'] || 'Untitled Question';
					$('.delete-dialog .delete-name').text(text);
					$('.delete-dialog .cancel-delete-button').unbind('click').bind('click', cmui.hideDialog);
					$('.delete-dialog .soft-delete-button').unbind('click').bind('click', function() {
						cmui.hideDialog();
						tr.addClass('inactive');
						editor.find('.ea-active').prop('checked', false);
					});
					$('.delete-dialog .confirm-delete-button').unbind('click').bind('click', function() {
						cmui.hideDialog();
						if (isNew) {
							tr.remove();
							editor.remove();
						} else {
							doAjax('Saving...', {
								'cm-form-action': 'delete-question',
								'cm-form-question-id': question['question-id']
							}, function() {
								tr.remove();
								editor.remove();
							});
						}
					});
					cmui.showDialog('delete');
				});
				$('*').blur();
				editor.find('.ea-title').focus();
			});
		};
		var doLoad = function() {
			doAjax('Loading...', {'cm-form-action': 'list-questions'}, function(response) {
				var tbody = $('.cm-form-editor-dynamic-section').empty();
				for (var i = 0, n = response.questions.length; i < n; i++) {
					var tr = $(response.html[i]);
					var question = response.questions[i];
					prepDynamicRow(tbody, tr, question, false);
				}
			});
		};

		$('.cm-form-editor-dynamic-section-actions .add-button').bind('click', function() {
			var editor = $('.cm-form-editor-dynamic-section-editor tr');
			var questionId = {'question-id': 'NEW-' + new Date().getTime()};
			var question = pullFromEditor(editor, questionId);
			renderQuestion(question, function(html) {
				var tbody = $('.cm-form-editor-dynamic-section');
				var tr = $(html);
				prepDynamicRow(tbody, tr, question, true);
				tr.click();
			});
		});

		/* Keyboard Navigation */
		$('body').bind('keydown', function(event) {
			if (!$('.dialog-cover').hasClass('hidden')) return;
			switch (event.which) {
				case 27:
					$('.cancel-edit-button').click();
					break;
				case 38:
					if (!event.shiftKey || !(event.ctrlKey || event.metaKey)) return;
					var e = $('.up-button:visible');
					if (e.length == 1) e.click();
					break;
				case 40:
					if (!event.shiftKey || !(event.ctrlKey || event.metaKey)) return;
					var e = $('.down-button:visible');
					if (e.length == 1) e.click();
					break;
				case 65:
					if (!event.shiftKey || !(event.ctrlKey || event.metaKey)) return;
					var e = $('.add-button:visible');
					if (e.length == 1) e.click();
					break;
				case 68:
					if (!event.shiftKey || !(event.ctrlKey || event.metaKey)) return;
					var e = $('.delete-button:visible');
					if (e.length == 1) e.click();
					break;
				case 83:
					if (!event.shiftKey || !(event.ctrlKey || event.metaKey)) return;
					var e = $('.confirm-edit-button:visible');
					if (e.length == 1) e.click();
					break;
				case 88:
					if (!event.shiftKey || !(event.ctrlKey || event.metaKey)) return;
					var e = $('.ea-active:visible');
					if (e.length == 1) e.click();
					break;
				case 191:
					if (!event.shiftKey || !(event.ctrlKey || event.metaKey)) return;
					cmui.showDialog('shortcuts');
					break;
				default:
					return;
			}
			event.stopPropagation();
			event.preventDefault();
		});

		doLoad();
	});
})(jQuery,window,document,cmui,cm_form_def);