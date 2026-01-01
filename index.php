<?php
// M3U Playlist Customizer
// Handle playlist save request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_playlist'])) {
    $channels = json_decode($_POST['channels_data'], true);
    
    if ($channels) {
        // Generate M3U content
        $m3uContent = "#EXTM3U\n";
        
        foreach ($channels as $channel) {
            $tvgName = htmlspecialchars_decode($channel['tvg_name']);
            $tvgLogo = htmlspecialchars_decode($channel['tvg_logo']);
            $title = htmlspecialchars_decode($channel['title']);
            $url = htmlspecialchars_decode($channel['url']);
            
            // Build EXTINF line
            $extinfLine = '#EXTINF:-1';
            if (!empty($tvgName)) {
                $extinfLine .= ' tvg-name="' . $tvgName . '"';
            }
            if (!empty($tvgLogo)) {
                $extinfLine .= ' tvg-logo="' . $tvgLogo . '"';
            }
            $extinfLine .= ',' . $title . "\n";
            
            $m3uContent .= $extinfLine . $url . "\n";
        }
        
        // Save to file with random name
        $filename = uniqid('playlist_', true) . '.m3u';
        $filepath = __DIR__ . '/playlists/' . $filename;
        
        if (file_put_contents($filepath, $m3uContent)) {
            $successMessage = "Playlist saved successfully as: " . $filename;
            $downloadLink = 'playlists/' . $filename;
        } else {
            $errorMessage = "Error saving playlist file.";
        }
    }
}

// Handle playlist loading
$channels = [];
$loadError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['load_playlist']) && !empty($_POST['playlist_url'])) {
    $playlistUrl = $_POST['playlist_url'];
    
    // Fetch M3U content
    $m3uContent = @file_get_contents($playlistUrl);
    
    if ($m3uContent === false) {
        $loadError = "Error: Could not load playlist from URL.";
    } else {
        // Parse M3U content
        $lines = explode("\n", $m3uContent);
        $currentChannel = null;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line)) continue;
            
            if (strpos($line, '#EXTINF:') === 0) {
                // Parse EXTINF line
                $currentChannel = [
                    'tvg_name' => '',
                    'tvg_logo' => '',
                    'title' => '',
                    'url' => ''
                ];
                
                // Extract tvg-name
                if (preg_match('/tvg-name="([^"]*)"/', $line, $matches)) {
                    $currentChannel['tvg_name'] = $matches[1];
                }
                
                // Extract tvg-logo
                if (preg_match('/tvg-logo="([^"]*)"/', $line, $matches)) {
                    $currentChannel['tvg_logo'] = $matches[1];
                }
                
                // Extract title (after last comma)
                if (preg_match('/,(.*)$/', $line, $matches)) {
                    $currentChannel['title'] = trim($matches[1]);
                }
                
            } elseif ($currentChannel !== null && !empty($line) && strpos($line, '#') !== 0) {
                // This is the URL line
                $currentChannel['url'] = $line;
                $channels[] = $currentChannel;
                $currentChannel = null;
            }
        }
        
        // Sort channels by title
        usort($channels, function($a, $b) {
            return strcasecmp($a['title'], $b['title']);
        });
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M3U Playlist Customizer</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 30px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 30px;
            text-align: center;
            font-size: 2.5em;
        }
        
        .load-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
        }
        
        input[type="text"], input[type="url"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus, input[type="url"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        button {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        button:hover {
            background: #5568d3;
        }
        
        .channel-list {
            margin-top: 30px;
        }
        
        .channel-item {
            background: #f8f9fa;
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        
        .channel-item h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.2em;
        }
        
        .channel-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .field-group {
            display: flex;
            flex-direction: column;
        }
        
        .field-group label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .save-section {
            margin-top: 30px;
            text-align: center;
            padding: 25px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .save-section button {
            background: #28a745;
            font-size: 18px;
            padding: 15px 50px;
        }
        
        .save-section button:hover {
            background: #218838;
        }
        
        .message {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .download-link {
            display: inline-block;
            margin-top: 10px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .download-link:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .channel-fields {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎬 M3U Playlist Customizer</h1>
        
        <?php if (isset($successMessage)): ?>
            <div class="message success">
                <?php echo $successMessage; ?>
                <br>
                <a href="<?php echo $downloadLink; ?>" class="download-link" download>📥 Download Playlist</a>
            </div>
        <?php endif; ?>
        
        <?php if (isset($errorMessage)): ?>
            <div class="message error"><?php echo $errorMessage; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($loadError)): ?>
            <div class="message error"><?php echo $loadError; ?></div>
        <?php endif; ?>
        
        <div class="load-section">
            <form method="POST">
                <div class="form-group">
                    <label for="playlist_url">M3U Playlist URL:</label>
                    <input type="url" 
                           id="playlist_url" 
                           name="playlist_url" 
                           placeholder="https://example.com/playlist.m3u" 
                           required
                           value="<?php echo isset($_POST['playlist_url']) ? htmlspecialchars($_POST['playlist_url']) : ''; ?>">
                </div>
                <button type="submit" name="load_playlist">Load Playlist</button>
            </form>
        </div>
        
        <?php if (!empty($channels)): ?>
            <form method="POST" id="channelForm">
                <div class="channel-list">
                    <h2 style="margin-bottom: 20px; color: #333;">Channels (<?php echo count($channels); ?>)</h2>
                    
                    <?php foreach ($channels as $index => $channel): ?>
                        <div class="channel-item">
                            <h3>Channel #<?php echo ($index + 1); ?></h3>
                            <div class="channel-fields">
                                <div class="field-group">
                                    <label>Channel Title:</label>
                                    <input type="text" 
                                           name="channels[<?php echo $index; ?>][title]" 
                                           class="channel-input"
                                           data-index="<?php echo $index; ?>"
                                           data-field="title"
                                           value="<?php echo htmlspecialchars($channel['title']); ?>">
                                </div>
                                <div class="field-group">
                                    <label>TVG Logo URL:</label>
                                    <input type="text" 
                                           name="channels[<?php echo $index; ?>][tvg_logo]" 
                                           class="channel-input"
                                           data-index="<?php echo $index; ?>"
                                           data-field="tvg_logo"
                                           value="<?php echo htmlspecialchars($channel['tvg_logo']); ?>">
                                </div>
                            </div>
                            <input type="hidden" 
                                   name="channels[<?php echo $index; ?>][tvg_name]" 
                                   value="<?php echo htmlspecialchars($channel['tvg_name']); ?>">
                            <input type="hidden" 
                                   name="channels[<?php echo $index; ?>][url]" 
                                   value="<?php echo htmlspecialchars($channel['url']); ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="save-section">
                    <input type="hidden" name="channels_data" id="channels_data">
                    <button type="submit" name="save_playlist" onclick="return prepareSubmit()">💾 Save As Playlist</button>
                </div>
            </form>
            
            <script>
                // Store initial channel data
                const channelsData = <?php echo json_encode($channels); ?>;
                
                // Update channel data when inputs change
                document.querySelectorAll('.channel-input').forEach(input => {
                    input.addEventListener('input', function() {
                        const index = parseInt(this.dataset.index);
                        const field = this.dataset.field;
                        channelsData[index][field] = this.value;
                    });
                });
                
                // Prepare data before form submission
                function prepareSubmit() {
                    document.getElementById('channels_data').value = JSON.stringify(channelsData);
                    return true;
                }
            </script>
        <?php endif; ?>
    </div>
</body>
</html>
