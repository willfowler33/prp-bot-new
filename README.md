# TCP Tech Bot WordPress Plugin

A professional WordPress plugin that integrates your Skald AI assistant chatbot directly into your WordPress website with a modern, responsive chat interface.

## Quick Start

1. **Install the plugin** in your WordPress site
2. **Activate it** from the Plugins page
3. Go to **TCP Tech Bot** in your admin sidebar
4. Enter your API key: `sk_proj_6240b81b7844c32374889f7ef5d0f2631d5e7f17`
5. Click **Save Settings**
6. Add `[tcp_tech_chat]` shortcode to any page
7. Done! Your chatbot is live with source citations enabled.

## Features

### Core Features
- **Easy Integration**: Connect your Skald AI assistant with just your API key
- **Multiple Display Options**: Floating chat button or inline embed
- **Responsive Design**: Works beautifully on desktop and mobile devices
- **Secure**: API key stored securely on the server (never exposed to frontend)
- **Customizable**: Configure welcome message, system prompt, and more
- **Modern UI**: Clean, professional chat interface with typing indicators
- **TCP Tech Branding**: Branded with TCP Tech blue colors (#002b5b, #2589bd)

### Advanced Features
- **📊 Chat Logging**: Store all chat interactions in database for analysis
- **⚡ Fast Responses**: Powered by Skald's AI technology
- **📈 Analytics Dashboard**: View metrics and response times
- **📤 CSV Export**: Export chat logs for analysis and training
- **🎨 Draggable & Resizable**: Users can move and resize the chat window
- **📱 Mobile Responsive**: Optimized for all screen sizes

## Installation

### Option 1: Manual Installation (Recommended for Development)

1. Download or clone this plugin folder
2. Copy the entire `tcp-tech-bot-wordpress` folder to your WordPress installation at:
   ```
   /wp-content/plugins/tcp-tech-bot-wordpress/
   ```
3. Log in to your WordPress admin dashboard
4. Navigate to **Plugins > Installed Plugins**
5. Find "TCP Tech Bot" and click **Activate**

### Option 2: ZIP Installation

1. Compress the `tcp-tech-bot-wordpress` folder into a ZIP file
2. In WordPress admin, go to **Plugins > Add New > Upload Plugin**
3. Choose the ZIP file and click **Install Now**
4. Click **Activate Plugin**

## Configuration

### 1. Get Your Skald API Key

You'll need a Skald API key from [useskald.com](https://useskald.com):
- Sign up for a Skald account
- Create a project
- Get your project API key (format: `sk_proj_...`)

### 2. Access Settings

After activating the plugin:
1. Log into your WordPress admin dashboard
2. Look for **TCP Tech Bot** in the left sidebar menu (with a chat icon)
3. Click on **TCP Tech Bot** to open the settings page

### 3. Configure Settings

On the settings page, you'll see fields for:

#### Required Settings:
- **Skald API Key**
  - Enter your API key: `sk_proj_6240b81b7844c32374889f7ef5d0f2631d5e7f17`
  - This is stored securely on the server, never exposed to browsers

#### Optional Settings:
- **Project ID**: Your Skald project ID
  - Only needed if not using a project-specific API key
  - Can usually be left empty

- **Welcome Message**: The first message users see
  - Default: "Hello! How can I help you with TCP Tech today?"
  - Customize this to match your brand voice

- **System Prompt**: Custom instructions for the AI
  - Use this to customize how the AI responds
  - Example: "You are a helpful TCP Tech product expert..."

### 4. Save Settings

Click the blue **Save Settings** button at the bottom of the page.

## Usage

### Shortcode Options

#### Floating Chat Button (Default)

Add this shortcode to any page, post, or widget:

```
[tcp_tech_chat]
```

This creates a floating chat button in the bottom-right corner of the page.

#### Custom Button Text

```
[tcp_tech_chat button_text="Need Help?"]
```

#### Inline Chat (Embedded)

Display the chat interface directly inline on the page:

```
[tcp_tech_chat position="inline"]
```

### Where to Add Shortcodes

1. **Pages/Posts**: Add the shortcode directly in the page/post editor (Classic or Gutenberg)
2. **Widgets**: Use a Text or Shortcode widget
3. **Theme Templates**: Use `do_shortcode('[tcp_tech_chat]')` in your theme files
4. **Site-wide**: Add to your theme's footer.php for global availability

### Multiple Instances

You can add multiple chat widgets on different pages with different configurations.

## Admin Dashboard

The plugin provides two admin pages accessible from the WordPress admin menu:

### 1. Settings
Configure your API credentials and preferences.

### 2. Chat Logs
- View all chat interactions in a paginated table
- Export logs to CSV for external analysis
- See session IDs, timestamps, and response times
- Test database connectivity

## File Structure

```
tcp-tech-bot-wordpress/
├── tcp-tech-bot.php          # Main plugin file
├── css/
│   └── chat-widget.css       # Widget styles (TCP Tech blue theme)
├── js/
│   └── chat-widget.js        # Widget functionality
└── README.md                 # This file
```

## Customization

### Styling

To customize the appearance, you can override the CSS classes in your theme's stylesheet:

```css
/* Change primary color */
.tcp-tech-chat-button {
    background: #your-color !important;
}

/* Adjust chat window size */
.tcp-tech-chat-window {
    width: 400px !important;
    height: 650px !important;
}
```

### Positioning

For floating chat, adjust the position in your theme CSS:

```css
.tcp-tech-chat-floating {
    bottom: 30px !important;
    left: 30px !important; /* Move to bottom-left */
}
```

## Troubleshooting

### Common Issues

**Chat Not Appearing:**
- Verify plugin is activated
- Check shortcode is correct: `[tcp_tech_chat]`
- Clear browser and WordPress cache
- Check browser console for JavaScript errors

**API Errors:**
- Verify API key is correct (starts with `sk_proj_`)
- Check that your Skald project is active
- Ensure you have API credits available
- Test your API key directly using curl

**Chat Logs Not Storing:**
- Enable WP_DEBUG_LOG in wp-config.php
- Check if database table exists
- Use the "Test Database Insert" button on Chat Logs page

### Enable Debug Logging

Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Log file location: `wp-content/debug.log`

### Test API Connection

Test your Skald API with curl:
```bash
curl "https://api.useskald.com/api/v1/chat" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "query": "test message",
    "stream": false
  }'
```

## Security

- API keys are stored securely in WordPress database
- All API calls are made server-side via WordPress AJAX
- User inputs are sanitized before processing
- Nonce verification for all AJAX requests
- No sensitive data exposed to frontend

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- Active Skald account with API access
- jQuery (included with WordPress)

## API Documentation

For more information about Skald's API:
- [Skald API Documentation](https://docs.useskald.com/docs/api-reference/introduction)
- [Skald Chat Endpoint](https://docs.useskald.com/docs/api-reference/chat)

## Support

For issues or questions:

1. Check the troubleshooting section above
2. Review Skald API documentation
3. Check WordPress error logs
4. Verify all configuration settings

## Development

To modify the plugin:

1. Edit `tcp-tech-bot.php` for backend functionality
2. Edit `css/chat-widget.css` for styling
3. Edit `js/chat-widget.js` for frontend behavior
4. Test thoroughly before deploying to production

## Changelog

### Version 1.0.0 (Current)
- Initial release
- Skald AI integration
- Floating and inline chat modes
- Responsive design
- Admin configuration panel
- Chat logging with CSV export
- Draggable and resizable chat window
- TCP Tech branded color scheme (#002b5b, #2589bd)

## License

GPL v2 or later

## Credits

Built for TCP Tech using [Skald AI](https://useskald.com/)
