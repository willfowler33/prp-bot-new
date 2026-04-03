jQuery(document).ready(function($) {
    'use strict';

    class TCPTechChatWidget {
        constructor(container) {
            this.container = container;
            this.chatWindow = container.find('.tcp-tech-chat-window');
            this.messagesContainer = container.find('.tcp-tech-chat-messages');
            this.input = container.find('.tcp-tech-chat-input');
            this.sendButton = container.find('.tcp-tech-chat-send');
            this.chatButton = container.find('.tcp-tech-chat-button');
            this.closeButton = container.find('.tcp-tech-chat-close');
            this.minimizeButton = container.find('.tcp-tech-chat-minimize');
            this.maximizeButton = container.find('.tcp-tech-chat-maximize');
            this.header = container.find('.tcp-tech-chat-header');

            this.isProcessing = false;
            this.sessionId = this.generateSessionId();

            // Product manual URL mapping - keys match exact Skald memo titles (lowercase)
            this.documentUrls = {
                // Graniflex variations
                'cp graniflex manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP_Graniflex_Manual_2025.pdf',
                'graniflex with tape design': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/Graniflex%20Tape%20Design%20Manual_Updated-compressed.pdf',
                'graniflex with perfect poly 90': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Sheets/G-301%20%20SYSTEM_TDS_GRANIFLEX_WITH_PERFECTPOLY90_.pdf',
                'graniflex with graniseal': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Sheets/G-302%20%20SYSTEM_TDS_GRANIFLEX_WITH_GRANISEAL_.pdf',
                'graniflex with neat coat epoxy': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Sheets/G-303%20%20%20SYSTEM_TDS_GRANIFLEX_WITH_EPOXYNEATCOAT_.pdf',

                // Marble systems
                'marbleflex': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP_Graniflex_Manual_2025.pdf',
                'cp italian marble manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP_ItalianMarble_Manual_2025.pdf',
                'cp metallic marble': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP_MetallicMarble_Manual_2025.pdf',

                // Rustic Wood
                'cp rustic concrete wood': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP_RusticConcreteWood_Manual_2025.pdf',
                'rustic concrete wood interior': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Sheets/C-102_RUSTICCONCRETEWOOD_INTERIOR.pdf',
                'rustic concrete wood exterior': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Sheets/C-101_RUSTICCONCRETEWOOD_EXTERIOR.pdf',

                // Protector series
                'cp protector image manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP_ProtectorImages_Manual_Updated-compressed.pdf',
                'cp protector flake manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP_ProtectorFlake_Manual_2025.pdf',

                // Supporting manuals
                'cp repair manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP_Repair_Manual%20_Updated.-compressed.pdf',
                'cp prep manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP_Prep_Manual_Updated.-compressed.pdf',
                'cp polyhard manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP_PolyHardSL_Manual_Updated-compressed.pdf',

                // Texture systems
                'cp texture fusion manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP%20Texture%20Fusion%20Manual_2025.pdf',
                'cp spray texture manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP%20Spray%20Texture%20Manual_2025.pdf',

                // Sealer and specialty
                'cp sealer manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP%20Sealer%20Manual_2025.pdf',
                'cp esd manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP_ESD_Manual_2025.pdf',
                'cp elastastone manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP_Elastastone_Manual_2025.pdf',

                // Other systems
                '123 resinous epoxy manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/123%20ResinousEpoxy_Manual_2025.pdf',
                'grind and seal manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/Grind%20and%20Seal%20Manual_2025.pdf',
                'stamped overlay manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/Stamped%20Overlay_Manual_2025.pdf',
                'cp tuscan slate manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP_Tuscan_Manual_2025.pdf',
                'cp venetian stone manual': 'https://49577885.fs1.hubspotusercontent-na1.net/hubfs/49577885/Product%20System%20Manuals/CP_Venetian_Manual_2025.pdf'
            };

            // Window state
            this.isMaximized = false;
            this.isMinimized = false;
            this.originalDimensions = {
                width: 380,
                height: 600,
                bottom: 90,
                right: 20
            };

            // Drag and resize state
            this.isDragging = false;
            this.isResizing = false;
            this.dragStart = { x: 0, y: 0 };
            this.windowStart = { top: 0, left: 0 };
            this.resizeDirection = null;

            this.init();
        }

        generateSessionId() {
            return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        }

        init() {
            // Show welcome message
            this.addMessage('assistant', tcpTechChat.welcomeMessage);

            // Event listeners
            this.chatButton.on('click', () => this.toggleChat());
            this.closeButton.on('click', () => this.closeWindow());
            this.minimizeButton.on('click', () => this.minimizeWindow());
            this.maximizeButton.on('click', () => this.maximizeWindow());
            this.sendButton.on('click', () => this.sendMessage());

            this.input.on('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });

            // Auto-resize textarea
            this.input.on('input', () => this.autoResizeTextarea());

            // Drag functionality
            this.initDrag();

            // Resize functionality
            this.initResize();
        }

        toggleChat() {
            this.chatWindow.slideToggle(300);
        }

        autoResizeTextarea() {
            this.input.css('height', 'auto');
            const scrollHeight = this.input[0].scrollHeight;
            this.input.css('height', Math.min(scrollHeight, 120) + 'px');
        }

        addMessage(role, content, showTime = true, references = null) {
            const time = new Date().toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit'
            });

            const avatar = role === 'assistant' ? 'TT' : 'You';
            const roleClass = role === 'assistant' ? 'assistant' : 'user';

            // Remove citation numbers and empty brackets from content
            let cleanContent = content
                .replace(/\[\[\d+\]\]/g, '')  // Remove [[1]], [[2]], etc.
                .replace(/\[\d+\]/g, '')      // Remove [1], [2], etc.
                .replace(/\[\s*\]/g, '')      // Remove [] or [ ]
                .trim();

            // Remove Skald memo URLs from content (we'll show proper PDF links in references instead)
            cleanContent = cleanContent.replace(/https?:\/\/(app\.)?useskald\.com\/[^\s]*/g, '');

            // Format references as footnotes if available
            let referencesHtml = '';
            if (role === 'assistant' && references && references.length > 0) {
                console.log('REFERENCES:', references.length, 'references passed');

                // Deduplicate references by memo_title
                const uniqueRefs = [];
                const seen = new Set();
                references.forEach((ref) => {
                    const title = ref.memo_title || ref.title || ref.name;
                    console.log('Reference title:', title);
                    if (title && !seen.has(title.toLowerCase())) {
                        seen.add(title.toLowerCase());
                        uniqueRefs.push(ref);
                    }
                });

                console.log('UNIQUE REFS:', uniqueRefs.length);

                if (uniqueRefs.length > 0) {
                    referencesHtml = '<div class="tcp-tech-chat-references">';
                    referencesHtml += '<div class="tcp-tech-chat-references-title">Referenced Documents:</div>';

                    uniqueRefs.forEach((ref, index) => {
                        const title = ref.memo_title || ref.title || ref.name || `Document ${index + 1}`;

                        // Try to match title to our product manual URLs
                        let url = this.findDocumentUrl(title);
                        console.log('Title:', title, '→ URL:', url ? 'FOUND' : 'NOT FOUND');

                        // Only show references that have matching URLs
                        if (url) {
                            const escapedUrl = this.escapeHtml(url);
                            const escapedTitle = this.escapeHtml(title);
                            referencesHtml += `<div class="tcp-tech-chat-reference-item">
                                • <a href="${escapedUrl}" target="_blank" rel="noopener noreferrer" class="tcp-tech-chat-reference-link">
                                    ${escapedTitle}
                                </a>
                            </div>`;
                        }
                    });

                    referencesHtml += '</div>';
                }
                console.log('Final referencesHtml length:', referencesHtml.length);
            }

            // Add document library footer for assistant messages
            let footerHtml = '';
            if (role === 'assistant') {
                footerHtml = `<div class="tcp-tech-chat-footer">
                    While AI can be a helpful resource, it may occasionally make mistakes. If you have questions or need clarification regarding our coating systems, please contact one of our experts at <a href="tel:1-877-743-9732" class="tcp-tech-chat-reference-link">1-877-743-9732</a> or email <a href="mailto:web@theconcreteprotector.com" class="tcp-tech-chat-reference-link">web@theconcreteprotector.com</a>.<br><br>
                    For full access to technical and support documentation, visit <a href="https://theconcreteprotector.com/tcp-document-library/" target="_blank" rel="noopener noreferrer" class="tcp-tech-chat-reference-link">The Concrete Protector Document Library</a>.
                </div>`;
            }

            const messageHtml = `
                <div class="tcp-tech-chat-message ${roleClass}">
                    <div class="tcp-tech-chat-message-avatar">${avatar}</div>
                    <div class="tcp-tech-chat-message-content">
                        <div class="tcp-tech-chat-message-bubble">
                            ${this.formatMessage(cleanContent)}
                        </div>
                        ${referencesHtml}
                        ${footerHtml}
                        ${showTime ? `<div class="tcp-tech-chat-message-time">${time}</div>` : ''}
                    </div>
                </div>
            `;

            this.messagesContainer.append(messageHtml);
            this.scrollToBottom();
        }

        findDocumentUrl(title) {
            // Normalize to lowercase for matching
            const normalized = title.toLowerCase().trim();

            // Try exact match first
            if (this.documentUrls[normalized]) {
                return this.documentUrls[normalized];
            }

            // Try partial match - check if any key is contained in the title
            for (const [key, url] of Object.entries(this.documentUrls)) {
                if (normalized.includes(key) || key.includes(normalized)) {
                    return url;
                }
            }

            return null;
        }

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        formatMessage(content) {
            // Escape HTML
            const escaped = $('<div>').text(content).html();

            // Convert line breaks to <br>
            let formatted = escaped.replace(/\n/g, '<br>');

            // Convert markdown-style bold **text**
            formatted = formatted.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');

            // Convert markdown-style italic *text*
            formatted = formatted.replace(/\*(.+?)\*/g, '<em>$1</em>');

            // Convert URLs to links
            formatted = formatted.replace(
                /(https?:\/\/[^\s]+)/g,
                '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>'
            );

            return formatted;
        }

        showTypingIndicator() {
            const typingHtml = `
                <div class="tcp-tech-chat-message assistant tcp-tech-typing-wrapper">
                    <div class="tcp-tech-chat-message-avatar">TT</div>
                    <div class="tcp-tech-chat-message-content">
                        <div class="tcp-tech-chat-typing">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                </div>
            `;
            this.messagesContainer.append(typingHtml);
            this.scrollToBottom();
        }

        hideTypingIndicator() {
            this.messagesContainer.find('.tcp-tech-typing-wrapper').remove();
        }

        showError(message) {
            const errorHtml = `
                <div class="tcp-tech-chat-error">
                    <strong>Error:</strong> ${message}
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

        async sendMessage() {
            if (this.isProcessing) return;

            const message = this.input.val().trim();
            if (!message) return;

            // Add user message to UI
            this.addMessage('user', message);

            // Clear input
            this.input.val('');
            this.autoResizeTextarea();

            // Disable input while processing
            this.isProcessing = true;
            this.sendButton.prop('disabled', true);
            this.input.prop('disabled', true);

            // Show typing indicator
            this.showTypingIndicator();

            try {
                // Build form data for the streaming POST
                const formData = new FormData();
                formData.append('action', 'tcp_tech_chat_stream');
                formData.append('nonce', tcpTechChat.nonce);
                formData.append('message', message);
                formData.append('session_id', this.sessionId);

                const response = await fetch(tcpTechChat.ajaxurl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                });

                this.hideTypingIndicator();

                if (!response.ok) {
                    this.showError('Failed to get response from assistant');
                    return;
                }

                // Create an empty assistant message bubble for streaming
                const { bubbleEl } = this.addStreamingMessage();
                let fullText = '';

                // Read the SSE stream
                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;

                    buffer += decoder.decode(value, { stream: true });
                    const lines = buffer.split('\n');
                    buffer = lines.pop();

                    for (const line of lines) {
                        const trimmed = line.trim();
                        if (!trimmed.startsWith('data: ')) continue;

                        try {
                            const event = JSON.parse(trimmed.substring(6));

                            if (event.type === 'token' && event.content) {
                                fullText += event.content;
                                // Clean and render incrementally
                                let cleanContent = fullText
                                    .replace(/\[\[\d+\]\]/g, '')
                                    .replace(/\[\d+\]/g, '')
                                    .replace(/\[\s*\]/g, '')
                                    .replace(/https?:\/\/(app\.)?useskald\.com\/[^\s]*/g, '')
                                    .trim();
                                bubbleEl.innerHTML = this.formatMessage(cleanContent);
                                this.scrollToBottom();
                            } else if (event.type === 'meta') {
                                if (event.session_id) {
                                    this.sessionId = event.session_id;
                                }
                            } else if (event.type === 'error') {
                                this.showError(event.content || 'Streaming error');
                            }
                        } catch (e) {
                            // Skip malformed JSON
                        }
                    }
                }

                // After stream completes, add references and footer
                this.finalizeWidgetStreamedMessage(bubbleEl, fullText);

            } catch (error) {
                this.hideTypingIndicator();
                this.showError('Network error. Please try again.');
                console.error('Chat error:', error);
            } finally {
                // Re-enable input
                this.isProcessing = false;
                this.sendButton.prop('disabled', false);
                this.input.prop('disabled', false);
                this.input.focus();
            }
        }

        /**
         * Create an empty assistant message shell for streaming tokens into
         */
        addStreamingMessage() {
            const time = new Date().toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit'
            });

            const messageEl = document.createElement('div');
            messageEl.className = 'tcp-tech-chat-message assistant';
            messageEl.innerHTML = `
                <div class="tcp-tech-chat-message-avatar">TT</div>
                <div class="tcp-tech-chat-message-content">
                    <div class="tcp-tech-chat-message-bubble"></div>
                    <div class="tcp-tech-chat-message-time">${time}</div>
                </div>
            `;

            this.messagesContainer.append(messageEl);
            const bubbleEl = messageEl.querySelector('.tcp-tech-chat-message-bubble');
            return { bubbleEl, messageEl };
        }

        /**
         * Add footer to a streamed widget message after stream completes
         */
        finalizeWidgetStreamedMessage(bubbleEl, fullText) {
            const contentEl = bubbleEl.closest('.tcp-tech-chat-message-content');
            if (!contentEl) return;

            // Add the disclaimer footer
            const footerHtml = `<div class="tcp-tech-chat-footer">
                While AI can be a helpful resource, it may occasionally make mistakes. If you have questions or need clarification regarding our coating systems, please contact one of our experts at <a href="tel:1-877-743-9732" class="tcp-tech-chat-reference-link">1-877-743-9732</a> or email <a href="mailto:web@theconcreteprotector.com" class="tcp-tech-chat-reference-link">web@theconcreteprotector.com</a>.<br><br>
                For full access to technical and support documentation, visit <a href="https://theconcreteprotector.com/tcp-document-library/" target="_blank" rel="noopener noreferrer" class="tcp-tech-chat-reference-link">The Concrete Protector Document Library</a>.
            </div>`;

            // Insert footer after the bubble
            bubbleEl.insertAdjacentHTML('afterend', footerHtml);
        }

        closeWindow() {
            this.chatWindow.slideUp(300);
        }

        minimizeWindow() {
            if (this.isMaximized) {
                this.maximizeWindow(); // Restore first if maximized
            }

            this.isMinimized = !this.isMinimized;

            if (this.isMinimized) {
                this.chatWindow.addClass('minimized');
                // Change icon to restore/expand
                this.minimizeButton.find('svg').html(`
                    <polyline points="18 15 12 9 6 15"></polyline>
                `);
            } else {
                this.chatWindow.removeClass('minimized');
                // Change icon back to minimize
                this.minimizeButton.find('svg').html(`
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                `);
            }
        }

        maximizeWindow() {
            this.isMaximized = !this.isMaximized;

            if (this.isMaximized) {
                // Save current dimensions before maximizing
                const rect = this.chatWindow[0].getBoundingClientRect();
                const currentStyle = this.chatWindow[0].style;

                this.originalDimensions = {
                    width: rect.width,
                    height: rect.height,
                    top: currentStyle.top || '',
                    left: currentStyle.left || '',
                    bottom: currentStyle.bottom || '',
                    right: currentStyle.right || ''
                };

                this.chatWindow.addClass('maximized');

                // Change icon to restore (two overlapping squares)
                this.maximizeButton.find('svg').html(`
                    <rect x="3" y="7" width="14" height="14" rx="1" ry="1" fill="none"></rect>
                    <polyline points="7 7 7 3 21 3 21 17 17 17" fill="none"></polyline>
                `);
            } else {
                // Restore original dimensions
                this.chatWindow.removeClass('maximized');

                // Only restore if we have saved dimensions
                if (this.originalDimensions.width) {
                    this.chatWindow.css({
                        width: this.originalDimensions.width + 'px',
                        height: this.originalDimensions.height + 'px',
                        top: this.originalDimensions.top,
                        left: this.originalDimensions.left,
                        bottom: this.originalDimensions.bottom,
                        right: this.originalDimensions.right
                    });
                }

                // Change icon back to maximize
                this.maximizeButton.find('svg').html(`
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2" fill="none"></rect>
                `);
            }
        }

        initDrag() {
            const self = this;

            this.header.on('mousedown', function(e) {
                // Don't drag if clicking on buttons
                if ($(e.target).closest('.tcp-tech-window-control').length) {
                    return;
                }

                // Don't drag if maximized or minimized
                if (self.isMaximized || self.isMinimized) {
                    return;
                }

                self.isDragging = true;
                self.chatWindow.addClass('dragging');

                // Store initial mouse position
                self.dragStart.x = e.clientX;
                self.dragStart.y = e.clientY;

                // Get current window position
                const rect = self.chatWindow[0].getBoundingClientRect();
                self.windowStart.top = rect.top;
                self.windowStart.left = rect.left;

                // Convert from bottom/right to top/left positioning
                self.chatWindow.css({
                    top: rect.top + 'px',
                    left: rect.left + 'px',
                    bottom: 'auto',
                    right: 'auto'
                });

                e.preventDefault();
            });

            $(document).on('mousemove', function(e) {
                if (!self.isDragging) return;

                const deltaX = e.clientX - self.dragStart.x;
                const deltaY = e.clientY - self.dragStart.y;

                const newTop = self.windowStart.top + deltaY;
                const newLeft = self.windowStart.left + deltaX;

                self.chatWindow.css({
                    top: newTop + 'px',
                    left: newLeft + 'px'
                });
            });

            $(document).on('mouseup', function() {
                if (self.isDragging) {
                    self.isDragging = false;
                    self.chatWindow.removeClass('dragging');
                }
            });
        }

        initResize() {
            const self = this;
            const resizeHandles = this.container.find('.tcp-tech-resize-handle');

            resizeHandles.on('mousedown', function(e) {
                if (self.isMaximized || self.isMinimized) {
                    return;
                }

                self.isResizing = true;
                self.chatWindow.addClass('resizing');

                // Determine resize direction from class
                const classList = $(this).attr('class').split(' ');
                const resizeClass = classList.find(cls => cls.startsWith('tcp-tech-resize-') && cls !== 'tcp-tech-resize-handle');
                if (resizeClass) {
                    self.resizeDirection = resizeClass.replace('tcp-tech-resize-', '');
                } else {
                    console.error('Could not determine resize direction');
                    return;
                }

                self.dragStart.x = e.clientX;
                self.dragStart.y = e.clientY;

                const rect = self.chatWindow[0].getBoundingClientRect();
                self.windowStart = {
                    width: rect.width,
                    height: rect.height,
                    top: rect.top,
                    left: rect.left
                };

                // Convert to top/left positioning if using bottom/right
                self.chatWindow.css({
                    top: rect.top + 'px',
                    left: rect.left + 'px',
                    bottom: 'auto',
                    right: 'auto',
                    width: rect.width + 'px',
                    height: rect.height + 'px'
                });

                e.preventDefault();
                e.stopPropagation();
            });

            $(document).on('mousemove', function(e) {
                if (!self.isResizing) return;

                const deltaX = e.clientX - self.dragStart.x;
                const deltaY = e.clientY - self.dragStart.y;

                let newWidth = self.windowStart.width;
                let newHeight = self.windowStart.height;
                let newTop = self.windowStart.top;
                let newLeft = self.windowStart.left;

                const minWidth = 300;
                const minHeight = 400;

                // Handle different resize directions
                if (self.resizeDirection.includes('e')) {
                    newWidth = Math.max(minWidth, self.windowStart.width + deltaX);
                }
                if (self.resizeDirection.includes('w')) {
                    newWidth = Math.max(minWidth, self.windowStart.width - deltaX);
                    if (newWidth > minWidth) {
                        newLeft = self.windowStart.left + deltaX;
                    }
                }
                if (self.resizeDirection.includes('s')) {
                    newHeight = Math.max(minHeight, self.windowStart.height + deltaY);
                }
                if (self.resizeDirection.includes('n')) {
                    newHeight = Math.max(minHeight, self.windowStart.height - deltaY);
                    if (newHeight > minHeight) {
                        newTop = self.windowStart.top + deltaY;
                    }
                }

                self.chatWindow.css({
                    width: newWidth + 'px',
                    height: newHeight + 'px',
                    top: newTop + 'px',
                    left: newLeft + 'px'
                });
            });

            $(document).on('mouseup', function() {
                if (self.isResizing) {
                    self.isResizing = false;
                    self.chatWindow.removeClass('resizing');
                    self.resizeDirection = null;
                }
            });
        }

        callSkaldAPI(message) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: tcpTechChat.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'tcp_tech_chat',
                        nonce: tcpTechChat.nonce,
                        message: message,
                        session_id: this.sessionId
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

    // Initialize all chat widgets on the page
    $('.tcp-tech-chat-container').each(function() {
        new TCPTechChatWidget($(this));
    });
});
