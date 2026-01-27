/**
 * PRP Bot Full-Screen Chat JavaScript
 * Handles conversation management, message sending, and UI interactions
 */

jQuery(document).ready(function($) {
    'use strict';

    class PRPFullscreenChat {
        constructor() {
            // DOM Elements
            this.app = $('.prp-chat-app');
            this.sidebar = this.app.find('.prp-sidebar');
            this.sidebarToggle = this.app.find('.prp-sidebar-toggle');
            this.sidebarOverlay = this.app.find('.prp-sidebar-overlay');
            this.newChatBtn = this.app.find('.prp-new-chat-btn');
            this.conversationList = this.app.find('.prp-conversation-list');
            this.conversationTitle = this.app.find('.prp-conversation-title');
            this.messagesContainer = this.app.find('.prp-messages-container');
            this.inputTextarea = this.app.find('.prp-input');
            this.sendBtn = this.app.find('.prp-send-btn');

            // State
            this.currentConversationId = null;
            this.conversations = [];
            this.isProcessing = false;
            this.isMobile = window.innerWidth <= 768;

            // Product manual URL mapping (same as widget)
            this.documentUrls = {
                'cp graniflex manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP_Graniflex_Manual_2025.pdf',
                'graniflex with tape design': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/Graniflex%20Tape%20Design%20Manual_Updated-compressed.pdf',
                'graniflex with perfect poly 90': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Sheets/G-301%20%20SYSTEM_TDS_GRANIFLEX_WITH_PERFECTPOLY90_.pdf',
                'graniflex with graniseal': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Sheets/G-302%20%20SYSTEM_TDS_GRANIFLEX_WITH_GRANISEAL_.pdf',
                'graniflex with neat coat epoxy': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Sheets/G-303%20%20%20SYSTEM_TDS_GRANIFLEX_WITH_EPOXYNEATCOAT_.pdf',
                'marbleflex': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP_Graniflex_Manual_2025.pdf',
                'cp italian marble manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP_ItalianMarble_Manual_2025.pdf',
                'cp metallic marble': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP_MetallicMarble_Manual_2025.pdf',
                'cp rustic concrete wood': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP_RusticConcreteWood_Manual_2025.pdf',
                'rustic concrete wood interior': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Sheets/C-102_RUSTICCONCRETEWOOD_INTERIOR.pdf',
                'rustic concrete wood exterior': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Sheets/C-101_RUSTICCONCRETEWOOD_EXTERIOR.pdf',
                'cp protector image manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP_ProtectorImages_Manual_Updated-compressed.pdf',
                'cp protector flake manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP_ProtectorFlake_Manual_2025.pdf',
                'cp repair manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP_Repair_Manual%20_Updated.-compressed.pdf',
                'cp prep manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP_Prep_Manual_Updated.-compressed.pdf',
                'cp polyhard manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP_PolyHardSL_Manual_Updated-compressed.pdf',
                'cp texture fusion manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP%20Texture%20Fusion%20Manual_2025.pdf',
                'cp spray texture manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP%20Spray%20Texture%20Manual_2025.pdf',
                'cp sealer manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP%20Sealer%20Manual_2025.pdf',
                'cp esd manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP_ESD_Manual_2025.pdf',
                'cp elastastone manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP_Elastastone_Manual_2025.pdf',
                '123 resinous epoxy manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/123%20ResinousEpoxy_Manual_2025.pdf',
                'grind and seal manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/Grind%20and%20Seal%20Manual_2025.pdf',
                'stamped overlay manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/Stamped%20Overlay_Manual_2025.pdf',
                'cp tuscan slate manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP_Tuscan_Manual_2025.pdf',
                'cp venetian stone manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP_Venetian_Manual_2025.pdf'
            };

            this.init();
        }

        init() {
            this.bindEvents();
            this.loadConversations();
            this.autoResizeTextarea();
            this.handleResize();
        }

        bindEvents() {
            const self = this;

            // New chat button
            this.newChatBtn.on('click', () => this.createNewChat());

            // Send message
            this.sendBtn.on('click', () => this.sendMessage());
            this.inputTextarea.on('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });

            // Auto-resize textarea
            this.inputTextarea.on('input', () => this.autoResizeTextarea());

            // Sidebar toggle (mobile)
            this.sidebarToggle.on('click', () => this.toggleSidebar());
            this.sidebarOverlay.on('click', () => this.closeSidebar());

            // Window resize
            $(window).on('resize', () => this.handleResize());

            // Conversation list delegation
            this.conversationList.on('click', '.prp-conversation-item', function(e) {
                if (!$(e.target).closest('.prp-conversation-actions').length) {
                    const conversationId = $(this).data('id');
                    self.selectConversation(conversationId);
                }
            });

            // Edit button
            this.conversationList.on('click', '.prp-action-edit', function(e) {
                e.stopPropagation();
                const item = $(this).closest('.prp-conversation-item');
                self.startEditTitle(item);
            });

            // Delete button
            this.conversationList.on('click', '.prp-action-delete', function(e) {
                e.stopPropagation();
                const conversationId = $(this).closest('.prp-conversation-item').data('id');
                self.confirmDeleteConversation(conversationId);
            });

            // Edit input events
            this.conversationList.on('keydown', '.prp-edit-input', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const item = $(this).closest('.prp-conversation-item');
                    self.saveEditTitle(item);
                } else if (e.key === 'Escape') {
                    const item = $(this).closest('.prp-conversation-item');
                    self.cancelEditTitle(item);
                }
            });

            this.conversationList.on('blur', '.prp-edit-input', function() {
                const item = $(this).closest('.prp-conversation-item');
                self.saveEditTitle(item);
            });

            // Copy button click handler
            this.messagesContainer.on('click', '.prp-copy-btn', function(e) {
                e.preventDefault();
                const btn = $(this);
                const content = btn.data('content');

                navigator.clipboard.writeText(content).then(() => {
                    btn.addClass('copied');
                    btn.find('span').text('Copied!');
                    setTimeout(() => {
                        btn.removeClass('copied');
                        btn.find('span').text('Copy');
                    }, 2000);
                }).catch(err => {
                    console.error('Failed to copy:', err);
                });
            });

            // Reference number click - open document
            this.messagesContainer.on('click', '.prp-ref-number', function(e) {
                const tooltip = $(this).find('.prp-ref-tooltip');
                const link = tooltip.find('.prp-ref-tooltip-link');
                if (link.length) {
                    // Find the document URL from the title
                    const title = tooltip.find('.prp-ref-tooltip-title').text();
                    const url = self.findDocumentUrl(title);
                    if (url) {
                        window.open(url, '_blank');
                    }
                }
            });
        }

        handleResize() {
            this.isMobile = window.innerWidth <= 768;
            if (!this.isMobile) {
                this.sidebar.removeClass('open');
            }
        }

        toggleSidebar() {
            this.sidebar.toggleClass('open');
        }

        closeSidebar() {
            this.sidebar.removeClass('open');
        }

        autoResizeTextarea() {
            const textarea = this.inputTextarea[0];
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 200) + 'px';
        }

        /**
         * Load all conversations for current user
         */
        async loadConversations() {
            try {
                const response = await this.apiCall('tcp_get_conversations');
                if (response.success) {
                    this.conversations = response.data.conversations || [];
                    this.renderConversationList();

                    // If no conversations, show empty state
                    if (this.conversations.length === 0) {
                        this.showEmptyState();
                    }
                }
            } catch (error) {
                console.error('Failed to load conversations:', error);
            }
        }

        /**
         * Render the conversation list in sidebar
         */
        renderConversationList() {
            const self = this;
            this.conversationList.empty();

            // Group conversations by date
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);
            const lastWeek = new Date(today);
            lastWeek.setDate(lastWeek.getDate() - 7);

            const groups = {
                today: [],
                yesterday: [],
                lastWeek: [],
                older: []
            };

            this.conversations.forEach(conv => {
                const convDate = new Date(conv.updated_at);
                convDate.setHours(0, 0, 0, 0);

                if (convDate.getTime() === today.getTime()) {
                    groups.today.push(conv);
                } else if (convDate.getTime() === yesterday.getTime()) {
                    groups.yesterday.push(conv);
                } else if (convDate >= lastWeek) {
                    groups.lastWeek.push(conv);
                } else {
                    groups.older.push(conv);
                }
            });

            // Render each group
            if (groups.today.length > 0) {
                this.renderConversationGroup('Today', groups.today);
            }
            if (groups.yesterday.length > 0) {
                this.renderConversationGroup('Yesterday', groups.yesterday);
            }
            if (groups.lastWeek.length > 0) {
                this.renderConversationGroup('Previous 7 Days', groups.lastWeek);
            }
            if (groups.older.length > 0) {
                this.renderConversationGroup('Older', groups.older);
            }
        }

        renderConversationGroup(title, conversations) {
            const group = $(`
                <div class="prp-conversation-group">
                    <div class="prp-conversation-group-title">${this.escapeHtml(title)}</div>
                </div>
            `);

            conversations.forEach(conv => {
                const isActive = conv.id === this.currentConversationId;
                const item = $(`
                    <div class="prp-conversation-item ${isActive ? 'active' : ''}" data-id="${conv.id}">
                        <svg class="prp-conversation-item-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                        <div class="prp-conversation-item-content">
                            <div class="prp-conversation-item-title">${this.escapeHtml(conv.title || 'New Chat')}</div>
                        </div>
                        <div class="prp-conversation-actions">
                            <button class="prp-conversation-action-btn prp-action-edit" title="Rename">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                            <button class="prp-conversation-action-btn prp-action-delete" title="Delete">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 6h18"></path>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                `);
                group.append(item);
            });

            this.conversationList.append(group);
        }

        /**
         * Select and load a conversation
         */
        async selectConversation(conversationId) {
            if (this.isProcessing) return;

            // Update UI immediately
            this.currentConversationId = conversationId;
            this.conversationList.find('.prp-conversation-item').removeClass('active');
            this.conversationList.find(`[data-id="${conversationId}"]`).addClass('active');

            // Close sidebar on mobile
            if (this.isMobile) {
                this.closeSidebar();
            }

            // Show loading
            this.messagesContainer.html('<div class="prp-loading"><div class="prp-loading-spinner"></div></div>');

            try {
                const response = await this.apiCall('tcp_get_conversation', {
                    conversation_id: conversationId
                });

                if (response.success && response.data.conversation) {
                    const conv = response.data.conversation;
                    this.conversationTitle.text(conv.title || 'New Chat');
                    this.renderMessages(conv.messages || []);
                } else {
                    const errorMsg = response.data || 'Conversation not found';
                    console.error('Failed to load conversation:', errorMsg);
                    this.showError(errorMsg);
                }
            } catch (error) {
                console.error('Failed to load conversation:', error);
                this.showError('Network error loading conversation');
            }
        }

        /**
         * Create a new chat conversation
         */
        async createNewChat() {
            if (this.isProcessing) return;

            try {
                const response = await this.apiCall('tcp_create_conversation');
                if (response.success && response.data.conversation) {
                    const conv = response.data.conversation;
                    this.conversations.unshift(conv);
                    this.currentConversationId = conv.id;
                    this.renderConversationList();
                    this.conversationTitle.text('New Chat');
                    this.showEmptyState();
                    this.inputTextarea.focus();

                    if (this.isMobile) {
                        this.closeSidebar();
                    }
                }
            } catch (error) {
                console.error('Failed to create conversation:', error);
            }
        }

        /**
         * Send a message
         */
        async sendMessage() {
            if (this.isProcessing) return;

            const message = this.inputTextarea.val().trim();
            if (!message) return;

            this.isProcessing = true;
            this.sendBtn.prop('disabled', true);
            this.inputTextarea.prop('disabled', true);

            // Clear input
            this.inputTextarea.val('');
            this.autoResizeTextarea();

            // Remove empty state if present
            this.messagesContainer.find('.prp-empty-state').remove();

            // Add user message to UI
            this.addMessage('user', message);

            // Show typing indicator
            this.showTypingIndicator();

            // Scroll to bottom
            this.scrollToBottom();

            try {
                const response = await this.apiCall('tcp_fullscreen_chat', {
                    message: message,
                    conversation_id: this.currentConversationId || ''
                });

                this.hideTypingIndicator();

                if (response.success) {
                    // Update conversation ID if new
                    if (response.data.conversation_id && !this.currentConversationId) {
                        this.currentConversationId = response.data.conversation_id;
                    }

                    // Update title if changed
                    if (response.data.conversation_title) {
                        this.conversationTitle.text(response.data.conversation_title);

                        // Update in list
                        const conv = this.conversations.find(c => c.id == this.currentConversationId);
                        if (conv) {
                            conv.title = response.data.conversation_title;
                        }
                        this.renderConversationList();
                    }

                    // Add assistant message
                    this.addMessage('assistant', response.data.message, response.data.references);
                } else {
                    this.showError(response.data || 'Failed to get response');
                }
            } catch (error) {
                this.hideTypingIndicator();
                this.showError('Network error. Please try again.');
                console.error('Chat error:', error);
            } finally {
                this.isProcessing = false;
                this.sendBtn.prop('disabled', false);
                this.inputTextarea.prop('disabled', false);
                this.inputTextarea.focus();
                this.scrollToBottom();
            }
        }

        /**
         * Add a message to the UI
         */
        addMessage(role, content, references = null) {
            const isUser = role === 'user';
            const avatarInitial = isUser ? prpChat.userName.charAt(0).toUpperCase() : 'P';
            const roleLabel = isUser ? 'You' : 'PRP Bot';

            // Store raw content for copy (just remove reference markers)
            let rawContent = content
                .replace(/\[\[\d+\]\]/g, '')
                .replace(/\[\d+\]/g, '')
                .replace(/<[^>]+>/g, '')
                .trim();

            // Get unique references for assistant messages
            const uniqueRefs = !isUser && references ? this.getUniqueReferences(references) : [];

            // Keep content as-is - only remove Skald internal URLs
            let cleanForFormat = content
                .replace(/https?:\/\/(app\.)?useskald\.com\/[^\s]*/g, '')
                .trim();

            // Format content first (this escapes HTML)
            let formattedContent = this.formatMessage(cleanForFormat);

            // Track which references are actually used
            const usedRefs = new Set();

            // Replace [[n]] or [n] with just the number
            if (!isUser && uniqueRefs.length > 0) {
                formattedContent = formattedContent.replace(/\[\[(\d+)\]\]|\[(\d+)\]/g, (match, num1, num2) => {
                    const refNum = parseInt(num1 || num2);
                    if (refNum >= 1 && refNum <= uniqueRefs.length) {
                        usedRefs.add(refNum);
                        return `<sup>${refNum}</sup>`;
                    }
                    return '';
                });
            }

            // Build simple references list at bottom
            let referencesHtml = '';
            if (!isUser && usedRefs.size > 0) {
                const sortedRefs = Array.from(usedRefs).sort((a, b) => a - b);
                let refItems = sortedRefs.map(refNum => {
                    const ref = uniqueRefs[refNum - 1];
                    const title = ref.memo_title || ref.title || ref.name || '';
                    return `<div class="prp-ref-item">[${refNum}] ${this.escapeHtml(title)}</div>`;
                }).join('');
                referencesHtml = `<div class="prp-references-section">${refItems}</div>`;
            }

            // Build copy button for assistant messages
            let actionsHtml = '';
            if (!isUser) {
                actionsHtml = `
                    <div class="prp-message-actions">
                        <button class="prp-copy-btn" data-content="${this.escapeHtml(rawContent).replace(/"/g, '&quot;')}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                            </svg>
                            <span>Copy</span>
                        </button>
                    </div>
                `;
            }

            const messageHtml = `
                <div class="prp-message-wrapper">
                    <div class="prp-message ${role}">
                        <div class="prp-message-header">
                            <div class="prp-message-avatar">${avatarInitial}</div>
                            <div class="prp-message-role">${roleLabel}</div>
                        </div>
                        <div class="prp-message-content">
                            ${formattedContent}
                            ${referencesHtml}
                        </div>
                        ${actionsHtml}
                    </div>
                </div>
            `;

            this.messagesContainer.append(messageHtml);
        }

        /**
         * Render all messages for a conversation
         */
        renderMessages(messages) {
            this.messagesContainer.empty();

            if (messages.length === 0) {
                this.showEmptyState();
                return;
            }

            messages.forEach(msg => {
                // Add user message
                this.addMessage('user', msg.user_message);

                // Add assistant response with references
                const refs = msg.metadata && msg.metadata.references ? msg.metadata.references : null;
                this.addMessage('assistant', msg.assistant_response, refs);
            });

            this.scrollToBottom();
        }

        /**
         * Show empty state for new chat
         */
        showEmptyState() {
            const emptyHtml = `
                <div class="prp-empty-state">
                    <svg class="prp-empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <h2 class="prp-empty-state-title">How can I help you today?</h2>
                    <p class="prp-empty-state-subtitle">${prpChat.welcomeMessage}</p>
                </div>
            `;
            this.messagesContainer.html(emptyHtml);
        }

        /**
         * Show typing indicator
         */
        showTypingIndicator() {
            const typingHtml = `
                <div class="prp-message-wrapper prp-typing-wrapper">
                    <div class="prp-message assistant">
                        <div class="prp-message-header">
                            <div class="prp-message-avatar">P</div>
                            <div class="prp-message-role">PRP Bot</div>
                        </div>
                        <div class="prp-typing-indicator">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                </div>
            `;
            this.messagesContainer.append(typingHtml);
        }

        hideTypingIndicator() {
            this.messagesContainer.find('.prp-typing-wrapper').remove();
        }

        /**
         * Show error message
         */
        showError(message) {
            const errorHtml = `
                <div class="prp-error-message">
                    <div class="prp-error-content">
                        <strong>Error:</strong> ${this.escapeHtml(message)}
                    </div>
                </div>
            `;
            this.messagesContainer.append(errorHtml);
            this.scrollToBottom();
        }

        scrollToBottom() {
            this.messagesContainer.animate({
                scrollTop: this.messagesContainer[0].scrollHeight
            }, 300);
        }

        /**
         * Start editing conversation title
         */
        startEditTitle(item) {
            const titleEl = item.find('.prp-conversation-item-title');
            const currentTitle = titleEl.text();
            titleEl.html(`<input type="text" class="prp-edit-input" value="${this.escapeHtml(currentTitle)}">`);
            titleEl.find('input').focus().select();
        }

        /**
         * Save edited title
         */
        async saveEditTitle(item) {
            const input = item.find('.prp-edit-input');
            if (input.length === 0) return;

            const newTitle = input.val().trim();
            const conversationId = item.data('id');

            if (!newTitle) {
                this.cancelEditTitle(item);
                return;
            }

            item.find('.prp-conversation-item-title').text(newTitle);

            try {
                await this.apiCall('tcp_rename_conversation', {
                    conversation_id: conversationId,
                    title: newTitle
                });

                // Update in local state
                const conv = this.conversations.find(c => c.id == conversationId);
                if (conv) {
                    conv.title = newTitle;
                }

                // Update header if current
                if (conversationId == this.currentConversationId) {
                    this.conversationTitle.text(newTitle);
                }
            } catch (error) {
                console.error('Failed to rename conversation:', error);
            }
        }

        cancelEditTitle(item) {
            const conv = this.conversations.find(c => c.id == item.data('id'));
            item.find('.prp-conversation-item-title').text(conv ? conv.title : 'New Chat');
        }

        /**
         * Show delete confirmation dialog
         */
        confirmDeleteConversation(conversationId) {
            const self = this;
            const dialog = $(`
                <div class="prp-confirm-dialog">
                    <div class="prp-confirm-content">
                        <h3 class="prp-confirm-title">Delete chat?</h3>
                        <p class="prp-confirm-message">This will delete the conversation and all its messages. This action cannot be undone.</p>
                        <div class="prp-confirm-actions">
                            <button class="prp-confirm-btn prp-confirm-btn-cancel">Cancel</button>
                            <button class="prp-confirm-btn prp-confirm-btn-delete">Delete</button>
                        </div>
                    </div>
                </div>
            `);

            dialog.find('.prp-confirm-btn-cancel').on('click', () => dialog.remove());
            dialog.find('.prp-confirm-btn-delete').on('click', () => {
                dialog.remove();
                self.deleteConversation(conversationId);
            });

            $('body').append(dialog);
        }

        /**
         * Delete a conversation
         */
        async deleteConversation(conversationId) {
            try {
                const response = await this.apiCall('tcp_delete_conversation', {
                    conversation_id: conversationId
                });

                if (response.success) {
                    // Remove from local state
                    this.conversations = this.conversations.filter(c => c.id != conversationId);
                    this.renderConversationList();

                    // If deleted current conversation, clear or load another
                    if (conversationId == this.currentConversationId) {
                        this.currentConversationId = null;
                        if (this.conversations.length > 0) {
                            this.selectConversation(this.conversations[0].id);
                        } else {
                            this.conversationTitle.text('New Chat');
                            this.showEmptyState();
                        }
                    }
                }
            } catch (error) {
                console.error('Failed to delete conversation:', error);
            }
        }

        /**
         * Format message content (basic markdown)
         */
        formatMessage(content) {
            // Escape HTML first
            let formatted = this.escapeHtml(content);

            // Convert line breaks
            formatted = formatted.replace(/\n\n/g, '</p><p>');
            formatted = formatted.replace(/\n/g, '<br>');

            // Bold **text**
            formatted = formatted.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');

            // Italic *text*
            formatted = formatted.replace(/\*(.+?)\*/g, '<em>$1</em>');

            // Inline code `code`
            formatted = formatted.replace(/`([^`]+)`/g, '<code>$1</code>');

            // URLs
            formatted = formatted.replace(
                /(https?:\/\/[^\s<]+)/g,
                '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>'
            );

            return '<p>' + formatted + '</p>';
        }

        /**
         * Find document URL by title
         */
        findDocumentUrl(title) {
            if (!title) return null;
            const normalized = title.toLowerCase().trim();

            if (this.documentUrls[normalized]) {
                return this.documentUrls[normalized];
            }

            for (const [key, url] of Object.entries(this.documentUrls)) {
                if (normalized.includes(key) || key.includes(normalized)) {
                    return url;
                }
            }

            return null;
        }

        /**
         * Get unique references by title
         */
        getUniqueReferences(references) {
            const seen = new Set();
            const unique = [];

            references.forEach(ref => {
                const title = ref.memo_title || ref.title || ref.name;
                if (title && !seen.has(title.toLowerCase())) {
                    seen.add(title.toLowerCase());
                    unique.push(ref);
                }
            });

            return unique;
        }

        /**
         * Escape HTML
         */
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        /**
         * Make API call
         */
        apiCall(action, data = {}) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: prpChat.ajaxurl,
                    type: 'POST',
                    data: {
                        action: action,
                        nonce: prpChat.nonce,
                        ...data
                    },
                    success: function(response) {
                        resolve(response);
                    },
                    error: function(xhr, status, error) {
                        reject(error);
                    }
                });
            });
        }
    }

    // Initialize if app container exists
    if ($('.prp-chat-app').length) {
        new PRPFullscreenChat();
    }
});
