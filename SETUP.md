# TCP Tech Bot - Complete Setup Guide

## Step-by-Step Installation

### Step 1: Upload to WordPress

**Option A: Via WordPress Admin (Recommended)**
1. Compress this entire folder into a ZIP file named `tcp-tech-bot.zip`
2. Log into your WordPress admin dashboard
3. Go to **Plugins > Add New**
4. Click **Upload Plugin** button at the top
5. Click **Choose File** and select `tcp-tech-bot.zip`
6. Click **Install Now**
7. Click **Activate Plugin**

**Option B: Via FTP/File Manager**
1. Upload the entire `tcp-tech-bot` folder to:
   ```
   /wp-content/plugins/
   ```
2. Log into WordPress admin
3. Go to **Plugins > Installed Plugins**
4. Find "TCP Tech Bot" and click **Activate**

### Step 2: Configure Your API Key

After activation, you'll see a new menu item in your WordPress admin:

1. **Look in the left sidebar** for "TCP Tech Bot" (it has a chat bubble icon 💬)
2. **Click on "TCP Tech Bot"** to open the settings page
3. You'll see the configuration form with these fields:

   **Skald API Key** (Required)
   ```
   sk_proj_6240b81b7844c32374889f7ef5d0f2631d5e7f17
   ```
   ↑ Enter your API key here (shown as password dots)

   **Project ID** (Optional)
   ```
   Leave blank unless specified
   ```

   **Welcome Message** (Optional)
   ```
   Default: "Hello! How can I help you with TCP Tech today?"
   Customize this to match your brand
   ```

   **System Prompt** (Optional)
   ```
   Example: "You are a helpful TCP Tech support assistant specializing in technical solutions..."
   ```

4. **Click the blue "Save Settings" button** at the bottom

### Step 3: Add Chat to Your Website

**Option 1: Floating Chat Button (Most Common)**

Add this shortcode to any page or post:
```
[tcp_tech_chat]
```

This creates a floating chat button in the bottom-right corner.

**Option 2: Custom Button Text**
```
[tcp_tech_chat button_text="Need Help?"]
```

**Option 3: Inline Embedded Chat**
```
[tcp_tech_chat position="inline"]
```

**Where to Add Shortcodes:**
- **Pages/Posts**: Click "Add Block" → Search "Shortcode" → Paste the code
- **Classic Editor**: Just paste the shortcode directly
- **Widgets**: Add a "Text" or "Shortcode" widget
- **Theme Files**: Use `<?php echo do_shortcode('[tcp_tech_chat]'); ?>`

### Step 4: Test Your Chat

1. Visit any page where you added the shortcode
2. Click the chat button
3. Type a test message: "What services does TCP Tech offer?"
4. You should see:
   - A typing indicator while AI responds
   - The AI response with your answer
   - **Source citations** as clickable footnotes below the response

## Features Included

✅ **References & Citations** - Every response includes source links
✅ **TCP Tech Colors** - Branded with #002b5b and #2589bd
✅ **Responsive Design** - Works on desktop, tablet, and mobile
✅ **Draggable Window** - Users can move the chat around
✅ **Resizable** - Users can resize the chat window
✅ **Chat Logs** - All conversations stored in database
✅ **CSV Export** - Download chat history for analysis

## Accessing Chat Logs

1. Go to **TCP Tech Bot > Chat Logs** in WordPress admin
2. View all conversations with timestamps
3. Click **Export to CSV** to download logs
4. Use for customer insights and AI training

## Customization

### Change Colors

Add this to your theme's `style.css`:

```css
/* Change primary button color */
.tcp-tech-chat-button {
    background: #YOUR_COLOR !important;
}

/* Change chat header */
.tcp-tech-chat-header {
    background: linear-gradient(135deg, #YOUR_COLOR1 0%, #YOUR_COLOR2 100%);
}
```

### Change Position

```css
/* Move to bottom-left */
.tcp-tech-chat-floating {
    left: 20px !important;
    right: auto !important;
}
```

## Troubleshooting

### "Can't find settings page"
- Make sure plugin is **activated** (not just installed)
- Look for "TCP Tech Bot" with chat icon in left sidebar
- Try refreshing the page or logging out/in

### "Chat not appearing"
- Check that shortcode is correct: `[tcp_tech_chat]`
- View page source and search for "tcp-tech-chat-container"
- Clear browser cache and WordPress cache
- Check browser console for JavaScript errors (F12)

### "API errors"
- Verify API key is correct (starts with `sk_proj_`)
- Check Skald account has credits available
- Test API key with curl:
  ```bash
  curl "https://api.useskald.com/api/v1/chat" \
    -H "Authorization: Bearer sk_proj_6240b81b7844c32374889f7ef5d0f2631d5e7f17" \
    -H "Content-Type: application/json" \
    -d '{"query": "test", "stream": false}'
  ```

### "No source citations showing"
- Citations only appear when Skald has sources to reference
- Try questions that require factual information
- Check that `rag_config` is enabled in code (it is by default)

## Advanced Configuration

### Enable Debug Mode

Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check logs at: `wp-content/debug.log`

### Database Tables

The plugin creates this table:
- `wp_tcp_tech_chat_logs` - Stores all chat interactions

## Support

For issues:
1. Check this guide's troubleshooting section
2. Review [Skald API Documentation](https://docs.useskald.com/)
3. Check WordPress error logs
4. Verify plugin files are complete and unmodified

## API Key Security

✅ **Your API key is secure**:
- Stored in WordPress database (not visible in page source)
- All API calls made server-side via WordPress AJAX
- Never exposed to browsers or frontend JavaScript
- Protected by WordPress nonce verification

---

**Need help?** Make sure you've:
- ✅ Activated the plugin
- ✅ Entered API key in settings
- ✅ Clicked "Save Settings"
- ✅ Added shortcode to a page
- ✅ Cleared cache and refreshed page
