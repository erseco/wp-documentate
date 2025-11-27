/**
 * Documentate Collaborative Editor
 *
 * TipTap editor with Yjs collaborative editing support via WebRTC.
 *
 * @package Documentate
 */

/* global documentateCollaborative */

(function () {
	'use strict';

	// CDN URLs for dependencies
	const CDN_BASE = 'https://esm.sh';
	const DEPENDENCIES = {
		yjs: `${CDN_BASE}/yjs@13.6.20`,
		yWebrtc: `${CDN_BASE}/y-webrtc@10.3.0`,
		tiptapCore: `${CDN_BASE}/@tiptap/core@2.11.8`,
		tiptapStarterKit: `${CDN_BASE}/@tiptap/starter-kit@2.11.8`,
		tiptapTable: `${CDN_BASE}/@tiptap/extension-table@2.11.8`,
		tiptapTableRow: `${CDN_BASE}/@tiptap/extension-table-row@2.11.8`,
		tiptapTableCell: `${CDN_BASE}/@tiptap/extension-table-cell@2.11.8`,
		tiptapTableHeader: `${CDN_BASE}/@tiptap/extension-table-header@2.11.8`,
		tiptapLink: `${CDN_BASE}/@tiptap/extension-link@2.11.8`,
		tiptapUnderline: `${CDN_BASE}/@tiptap/extension-underline@2.11.8`,
		tiptapTextAlign: `${CDN_BASE}/@tiptap/extension-text-align@2.11.8`,
		tiptapCollaboration: `${CDN_BASE}/@tiptap/extension-collaboration@2.11.8`,
		tiptapCollaborationCursor: `${CDN_BASE}/@tiptap/extension-collaboration-cursor@2.11.8`,
	};

	// Module cache
	let modules = null;

	// Active editors registry
	const editors = new Map();

	// Awareness colors for cursors
	const CURSOR_COLORS = [
		'#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4',
		'#FFEAA7', '#DDA0DD', '#98D8C8', '#F7DC6F',
	];

	/**
	 * Load all required modules from CDN.
	 *
	 * @return {Promise<Object>} Loaded modules.
	 */
	async function loadModules() {
		if (modules) {
			return modules;
		}

		try {
			const [
				Y,
				{ WebrtcProvider },
				{ Editor },
				StarterKit,
				Table,
				TableRow,
				TableCell,
				TableHeader,
				Link,
				Underline,
				TextAlign,
				Collaboration,
				CollaborationCursor,
			] = await Promise.all([
				import(DEPENDENCIES.yjs),
				import(DEPENDENCIES.yWebrtc),
				import(DEPENDENCIES.tiptapCore),
				import(DEPENDENCIES.tiptapStarterKit),
				import(DEPENDENCIES.tiptapTable),
				import(DEPENDENCIES.tiptapTableRow),
				import(DEPENDENCIES.tiptapTableCell),
				import(DEPENDENCIES.tiptapTableHeader),
				import(DEPENDENCIES.tiptapLink),
				import(DEPENDENCIES.tiptapUnderline),
				import(DEPENDENCIES.tiptapTextAlign),
				import(DEPENDENCIES.tiptapCollaboration),
				import(DEPENDENCIES.tiptapCollaborationCursor),
			]);

			modules = {
				Y,
				WebrtcProvider,
				Editor,
				StarterKit: StarterKit.default || StarterKit,
				Table: Table.default || Table,
				TableRow: TableRow.default || TableRow,
				TableCell: TableCell.default || TableCell,
				TableHeader: TableHeader.default || TableHeader,
				Link: Link.default || Link,
				Underline: Underline.default || Underline,
				TextAlign: TextAlign.default || TextAlign,
				Collaboration: Collaboration.default || Collaboration,
				CollaborationCursor: CollaborationCursor.default || CollaborationCursor,
			};

			return modules;
		} catch (error) {
			console.error('[Documentate] Failed to load collaborative editor modules:', error);
			throw error;
		}
	}

	/**
	 * Generate a random color for cursor.
	 *
	 * @return {string} Hex color.
	 */
	function getRandomColor() {
		return CURSOR_COLORS[Math.floor(Math.random() * CURSOR_COLORS.length)];
	}

	/**
	 * Get current user info from WordPress.
	 *
	 * @return {Object} User info with name and color.
	 */
	function getCurrentUser() {
		const config = window.documentateCollaborative || {};
		return {
			name: config.userName || 'Usuario',
			color: getRandomColor(),
		};
	}

	/**
	 * Create toolbar HTML for the editor.
	 *
	 * @param {string} editorId Editor identifier.
	 * @return {string} Toolbar HTML.
	 */
	function createToolbarHTML(editorId) {
		const prefix = `documentate-collab-${editorId}`;
		return `
			<div class="documentate-collab-toolbar" data-editor="${editorId}">
				<div class="documentate-collab-toolbar-group">
					<button type="button" class="documentate-collab-btn" data-action="bold" title="Negrita (Ctrl+B)">
						<strong>B</strong>
					</button>
					<button type="button" class="documentate-collab-btn" data-action="italic" title="Cursiva (Ctrl+I)">
						<em>I</em>
					</button>
					<button type="button" class="documentate-collab-btn" data-action="underline" title="Subrayado (Ctrl+U)">
						<u>U</u>
					</button>
				</div>
				<div class="documentate-collab-toolbar-separator"></div>
				<div class="documentate-collab-toolbar-group">
					<select class="documentate-collab-select" data-action="heading" title="Formato">
						<option value="paragraph">Párrafo</option>
						<option value="1">Título 1</option>
						<option value="2">Título 2</option>
						<option value="3">Título 3</option>
						<option value="4">Título 4</option>
					</select>
				</div>
				<div class="documentate-collab-toolbar-separator"></div>
				<div class="documentate-collab-toolbar-group">
					<button type="button" class="documentate-collab-btn" data-action="bulletList" title="Lista con viñetas">
						&#8226;
					</button>
					<button type="button" class="documentate-collab-btn" data-action="orderedList" title="Lista numerada">
						1.
					</button>
				</div>
				<div class="documentate-collab-toolbar-separator"></div>
				<div class="documentate-collab-toolbar-group">
					<button type="button" class="documentate-collab-btn" data-action="alignLeft" title="Alinear izquierda">
						&#8676;
					</button>
					<button type="button" class="documentate-collab-btn" data-action="alignCenter" title="Centrar">
						&#8596;
					</button>
					<button type="button" class="documentate-collab-btn" data-action="alignRight" title="Alinear derecha">
						&#8677;
					</button>
					<button type="button" class="documentate-collab-btn" data-action="alignJustify" title="Justificar">
						&#8700;
					</button>
				</div>
				<div class="documentate-collab-toolbar-separator"></div>
				<div class="documentate-collab-toolbar-group">
					<button type="button" class="documentate-collab-btn" data-action="link" title="Insertar enlace">
						&#128279;
					</button>
				</div>
				<div class="documentate-collab-toolbar-separator"></div>
				<div class="documentate-collab-toolbar-group">
					<button type="button" class="documentate-collab-btn" data-action="insertTable" title="Insertar tabla">
						&#9638;
					</button>
					<button type="button" class="documentate-collab-btn documentate-collab-btn-table" data-action="addColumnBefore" title="Añadir columna antes">
						&#8676;|
					</button>
					<button type="button" class="documentate-collab-btn documentate-collab-btn-table" data-action="addColumnAfter" title="Añadir columna después">
						|&#8677;
					</button>
					<button type="button" class="documentate-collab-btn documentate-collab-btn-table" data-action="deleteColumn" title="Eliminar columna">
						&#10006;|
					</button>
					<button type="button" class="documentate-collab-btn documentate-collab-btn-table" data-action="addRowBefore" title="Añadir fila antes">
						&#8593;
					</button>
					<button type="button" class="documentate-collab-btn documentate-collab-btn-table" data-action="addRowAfter" title="Añadir fila después">
						&#8595;
					</button>
					<button type="button" class="documentate-collab-btn documentate-collab-btn-table" data-action="deleteRow" title="Eliminar fila">
						&#10006;&#8212;
					</button>
					<button type="button" class="documentate-collab-btn documentate-collab-btn-table" data-action="deleteTable" title="Eliminar tabla">
						&#128465;
					</button>
					<button type="button" class="documentate-collab-btn documentate-collab-btn-table" data-action="mergeCells" title="Fusionar celdas">
						&#9641;&#9641;
					</button>
					<button type="button" class="documentate-collab-btn documentate-collab-btn-table" data-action="splitCell" title="Dividir celda">
						&#9639;
					</button>
				</div>
				<div class="documentate-collab-toolbar-separator"></div>
				<div class="documentate-collab-toolbar-group">
					<button type="button" class="documentate-collab-btn" data-action="undo" title="Deshacer (Ctrl+Z)">
						&#8630;
					</button>
					<button type="button" class="documentate-collab-btn" data-action="redo" title="Rehacer (Ctrl+Y)">
						&#8631;
					</button>
				</div>
				<div class="documentate-collab-toolbar-group documentate-collab-status">
					<span class="documentate-collab-connection" data-status="connecting">
						&#9679; Conectando...
					</span>
					<span class="documentate-collab-users"></span>
				</div>
			</div>
		`;
	}

	/**
	 * Setup toolbar event handlers.
	 *
	 * @param {HTMLElement} toolbar  Toolbar element.
	 * @param {Object}      editor   TipTap editor instance.
	 */
	function setupToolbarEvents(toolbar, editor) {
		// Button clicks
		toolbar.querySelectorAll('.documentate-collab-btn').forEach((btn) => {
			btn.addEventListener('click', (e) => {
				e.preventDefault();
				const action = btn.dataset.action;
				executeToolbarAction(editor, action);
			});
		});

		// Select changes
		toolbar.querySelectorAll('.documentate-collab-select').forEach((select) => {
			select.addEventListener('change', (e) => {
				const action = select.dataset.action;
				const value = select.value;
				executeToolbarAction(editor, action, value);
			});
		});

		// Update active states on selection change
		editor.on('selectionUpdate', () => updateToolbarState(toolbar, editor));
		editor.on('update', () => updateToolbarState(toolbar, editor));
	}

	/**
	 * Execute a toolbar action.
	 *
	 * @param {Object} editor TipTap editor.
	 * @param {string} action Action name.
	 * @param {string} value  Optional value.
	 */
	function executeToolbarAction(editor, action, value) {
		const chain = editor.chain().focus();

		switch (action) {
			case 'bold':
				chain.toggleBold().run();
				break;
			case 'italic':
				chain.toggleItalic().run();
				break;
			case 'underline':
				chain.toggleUnderline().run();
				break;
			case 'heading':
				if (value === 'paragraph') {
					chain.setParagraph().run();
				} else {
					chain.toggleHeading({ level: parseInt(value, 10) }).run();
				}
				break;
			case 'bulletList':
				chain.toggleBulletList().run();
				break;
			case 'orderedList':
				chain.toggleOrderedList().run();
				break;
			case 'alignLeft':
				chain.setTextAlign('left').run();
				break;
			case 'alignCenter':
				chain.setTextAlign('center').run();
				break;
			case 'alignRight':
				chain.setTextAlign('right').run();
				break;
			case 'alignJustify':
				chain.setTextAlign('justify').run();
				break;
			case 'link':
				const url = prompt('URL del enlace:');
				if (url) {
					chain.setLink({ href: url }).run();
				} else if (url === '') {
					chain.unsetLink().run();
				}
				break;
			case 'insertTable':
				chain.insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run();
				break;
			case 'addColumnBefore':
				chain.addColumnBefore().run();
				break;
			case 'addColumnAfter':
				chain.addColumnAfter().run();
				break;
			case 'deleteColumn':
				chain.deleteColumn().run();
				break;
			case 'addRowBefore':
				chain.addRowBefore().run();
				break;
			case 'addRowAfter':
				chain.addRowAfter().run();
				break;
			case 'deleteRow':
				chain.deleteRow().run();
				break;
			case 'deleteTable':
				chain.deleteTable().run();
				break;
			case 'mergeCells':
				chain.mergeCells().run();
				break;
			case 'splitCell':
				chain.splitCell().run();
				break;
			case 'undo':
				chain.undo().run();
				break;
			case 'redo':
				chain.redo().run();
				break;
		}
	}

	/**
	 * Update toolbar button states.
	 *
	 * @param {HTMLElement} toolbar Toolbar element.
	 * @param {Object}      editor  TipTap editor.
	 */
	function updateToolbarState(toolbar, editor) {
		// Update button active states
		const buttonStates = {
			bold: editor.isActive('bold'),
			italic: editor.isActive('italic'),
			underline: editor.isActive('underline'),
			bulletList: editor.isActive('bulletList'),
			orderedList: editor.isActive('orderedList'),
			alignLeft: editor.isActive({ textAlign: 'left' }),
			alignCenter: editor.isActive({ textAlign: 'center' }),
			alignRight: editor.isActive({ textAlign: 'right' }),
			alignJustify: editor.isActive({ textAlign: 'justify' }),
		};

		toolbar.querySelectorAll('.documentate-collab-btn').forEach((btn) => {
			const action = btn.dataset.action;
			if (buttonStates.hasOwnProperty(action)) {
				btn.classList.toggle('active', buttonStates[action]);
			}
		});

		// Update heading select
		const headingSelect = toolbar.querySelector('[data-action="heading"]');
		if (headingSelect) {
			let currentValue = 'paragraph';
			for (let i = 1; i <= 4; i++) {
				if (editor.isActive('heading', { level: i })) {
					currentValue = String(i);
					break;
				}
			}
			headingSelect.value = currentValue;
		}

		// Update table buttons visibility
		const isInTable = editor.isActive('table');
		toolbar.querySelectorAll('.documentate-collab-btn-table').forEach((btn) => {
			btn.style.display = isInTable ? '' : 'none';
		});
	}

	/**
	 * Update connection status indicator.
	 *
	 * @param {HTMLElement} toolbar Toolbar element.
	 * @param {string}      status  Connection status.
	 * @param {number}      users   Number of connected users.
	 */
	function updateConnectionStatus(toolbar, status, users) {
		const statusEl = toolbar.querySelector('.documentate-collab-connection');
		const usersEl = toolbar.querySelector('.documentate-collab-users');

		if (statusEl) {
			statusEl.dataset.status = status;
			const messages = {
				connecting: '&#9679; Conectando...',
				connected: '&#9679; Conectado',
				disconnected: '&#9679; Desconectado',
			};
			statusEl.innerHTML = messages[status] || messages.connecting;
		}

		if (usersEl && users > 0) {
			usersEl.textContent = `(${users} usuario${users > 1 ? 's' : ''})`;
		} else if (usersEl) {
			usersEl.textContent = '';
		}
	}

	/**
	 * Initialize a collaborative editor.
	 *
	 * @param {HTMLElement} container Editor container element.
	 * @param {HTMLElement} textarea  Hidden textarea for form submission.
	 * @return {Promise<Object>} Editor instance info.
	 */
	async function initEditor(container, textarea) {
		const mods = await loadModules();
		const {
			Y,
			WebrtcProvider,
			Editor,
			StarterKit,
			Table,
			TableRow,
			TableCell,
			TableHeader,
			Link,
			Underline,
			TextAlign,
			Collaboration,
			CollaborationCursor,
		} = mods;

		const config = window.documentateCollaborative || {};
		const editorId = textarea.id || `editor-${Date.now()}`;
		const roomName = `documentate-${config.postId || 0}-${editorId}`;
		const signalingServer = config.signalingServer || 'wss://signaling.yjs.dev';

		// Create Yjs document
		const ydoc = new Y.Doc();
		const yXmlFragment = ydoc.getXmlFragment('prosemirror');

		// Create WebRTC provider
		const provider = new WebrtcProvider(roomName, ydoc, {
			signaling: [signalingServer],
		});

		// Get current user info
		const user = getCurrentUser();

		// Create editor container structure
		const wrapper = document.createElement('div');
		wrapper.className = 'documentate-collab-wrapper';
		wrapper.innerHTML = createToolbarHTML(editorId);

		const editorElement = document.createElement('div');
		editorElement.className = 'documentate-collab-editor';
		wrapper.appendChild(editorElement);

		// Insert wrapper before textarea
		textarea.parentNode.insertBefore(wrapper, textarea);
		textarea.style.display = 'none';

		// Get initial content from textarea
		const initialContent = textarea.value || '';

		// Create TipTap editor
		const editor = new Editor({
			element: editorElement,
			extensions: [
				StarterKit.configure({
					history: false, // Disable built-in history, Yjs handles it
				}),
				Underline,
				Link.configure({
					openOnClick: false,
					HTMLAttributes: {
						target: '_blank',
						rel: 'noopener noreferrer',
					},
				}),
				TextAlign.configure({
					types: ['heading', 'paragraph'],
				}),
				Table.configure({
					resizable: true,
					HTMLAttributes: {
						class: 'documentate-table',
					},
				}),
				TableRow,
				TableHeader,
				TableCell,
				Collaboration.configure({
					document: ydoc,
					fragment: yXmlFragment,
				}),
				CollaborationCursor.configure({
					provider: provider,
					user: user,
				}),
			],
			content: '',
			onUpdate: ({ editor }) => {
				// Sync content back to textarea for form submission
				textarea.value = editor.getHTML();
			},
		});

		// Set initial content if document is empty and we have content
		if (initialContent && yXmlFragment.length === 0) {
			// Wait a bit for other clients to sync
			setTimeout(() => {
				if (yXmlFragment.length === 0) {
					editor.commands.setContent(initialContent);
				}
			}, 500);
		}

		// Setup toolbar
		const toolbar = wrapper.querySelector('.documentate-collab-toolbar');
		setupToolbarEvents(toolbar, editor);

		// Monitor connection status
		let connectedUsers = 0;

		provider.on('synced', (synced) => {
			if (synced) {
				updateConnectionStatus(toolbar, 'connected', connectedUsers);
			}
		});

		provider.awareness.on('change', () => {
			const states = provider.awareness.getStates();
			connectedUsers = states.size;
			updateConnectionStatus(toolbar, 'connected', connectedUsers);
		});

		provider.on('status', (event) => {
			updateConnectionStatus(toolbar, event.connected ? 'connected' : 'disconnected', connectedUsers);
		});

		// Store editor instance
		const instance = {
			editor,
			ydoc,
			provider,
			wrapper,
			textarea,
		};
		editors.set(editorId, instance);

		return instance;
	}

	/**
	 * Destroy an editor instance.
	 *
	 * @param {string} editorId Editor identifier.
	 */
	function destroyEditor(editorId) {
		const instance = editors.get(editorId);
		if (!instance) {
			return;
		}

		instance.provider.destroy();
		instance.editor.destroy();
		instance.wrapper.remove();
		instance.textarea.style.display = '';

		editors.delete(editorId);
	}

	/**
	 * Initialize all collaborative editors on the page.
	 */
	async function initAllEditors() {
		const containers = document.querySelectorAll('.documentate-collab-container');

		for (const container of containers) {
			const textarea = container.querySelector('textarea');
			if (textarea && !editors.has(textarea.id)) {
				try {
					await initEditor(container, textarea);
				} catch (error) {
					console.error('[Documentate] Failed to initialize editor:', error);
					// Show textarea as fallback
					textarea.style.display = '';
				}
			}
		}
	}

	/**
	 * Public API
	 */
	window.DocumentateCollaborativeEditor = {
		init: initAllEditors,
		initEditor,
		destroyEditor,
		getEditor: (id) => editors.get(id),
	};

	// Auto-initialize on DOM ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAllEditors);
	} else {
		initAllEditors();
	}
})();
