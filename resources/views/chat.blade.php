<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Chatbot</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f4f8;
            color: #333;
            margin: 0;
        }

        .chat-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 1000px;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            margin: auto;
        }

        .chat-header {
            padding: 15px 20px;
            background-color: #007bff;
            color: #fff;
            font-size: 20px;
            text-align: center;
        }

        .chat-body {
            padding: 20px;
            overflow-y: auto;
            flex-grow: 1;
            background: #f9f9f9;
        }

        .chat-message {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .chat-message img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .bot-message, .user-message {
            padding: 10px 15px;
            border-radius: 15px;
            max-width: 70%;
            line-height: 1.4;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .bot-message {
            background: #e0e0e0;
            color: #333;
            align-self: flex-start;
        }

        .user-message {
            background: #007bff;
            color: #fff;
            align-self: flex-end;
        }

        .chat-footer {
            display: flex;
            padding: 15px;
            background-color: #fff;
            border-top: 1px solid #ddd;
        }

        #message {
            flex-grow: 1;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            outline: none;
            margin-right: 10px;
        }

        #message:focus {
            border-color: #80bdff;
            box-shadow: 0 0 8px rgba(0, 123, 255, 0.25);
        }

        button[type="submit"] {
            padding: 12px 20px;
            font-size: 16px;
            color: #fff;
            background-color: #007bff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button[type="submit"]:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">Bot</div>
        <div id="chatBody" class="chat-body"></div>
        <div class="chat-footer">
            <input type="text" id="message" name="message" placeholder="Type your message..." required>
            <button type="submit" id="sendButton">Send</button>
        </div>
    </div>

    <script>
        document.getElementById('sendButton').addEventListener('click', async function() {
            const messageInput = document.getElementById('message');
            const message = messageInput.value.trim();
            const chatBody = document.getElementById('chatBody');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            if (message === "") return;

            // Add user message to chat
            addMessageToChat(message, 'user-message', 'user');

            // Send message to server
            const response = await fetch('/chatbot', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ message })
            });

            if (response.ok) {
                const data = await response.json();
                addMessageToChat(formatBotResponse(data.response) || 'No response received.', 'bot-message', 'bot');
            } else {
                addMessageToChat('An error occurred.', 'bot-message', 'bot');
            }

            // Clear input field
            messageInput.value = "";
            chatBody.scrollTop = chatBody.scrollHeight; // Scroll to the bottom
        });

        // Helper function to add a message to the chat
        function addMessageToChat(message, className, sender) {
            const chatBody = document.getElementById('chatBody');
            const messageDiv = document.createElement('div');
            messageDiv.classList.add('chat-message', className);

            const userIcon = sender === 'user' ? 'https://img.icons8.com/color/48/000000/user-male-circle--v1.png' 
                                               : 'https://img.icons8.com/fluency/48/000000/chatbot.png';

            const messageImg = document.createElement('img');
            messageImg.src = userIcon;
            messageDiv.appendChild(messageImg);

            const messageText = document.createElement('div');
            messageText.innerHTML = message;  // Allow HTML formatting within bot response
            messageDiv.appendChild(messageText);

            chatBody.appendChild(messageDiv);
        }

        // Helper function to format bot response with basic markdown (like ** for bold, etc.)
        function formatBotResponse(text) {
            return text
                .replace(/\*\*(.*?)\*\*/g, "<b>$1</b>")  // bold
                .replace(/\*(.*?)\*/g, "<i>$1</i>")      // italics
                .replace(/\n/g, "<br>");                 // new lines
        }
    </script>
</body>
</html>
