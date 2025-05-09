<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userMessage = $_POST['message'];
    $userEmail = $_POST['email']; // Get user email from POST data
    $userType = $_POST['usertype']; // Get user type from POST data

    // Prepare data to send to the Python API
    $dataToSend = [
        'message' => $userMessage,  
        // 'user' => [
        //     'email' => $userEmail,
        //     'usertype' => $userType
        // ]
    ];

    // Call your Python API (using cURL)
    $pythonApiUrl = "https://d08a-34-106-56-39.ngrok-free.app/chat"; // Replace with your Python API endpoint
    $ch = curl_init($pythonApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dataToSend));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    echo $response; // Return the chatbot's reply to the frontend
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot Interface</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        /* Chatbox container with 50% width */
        .chat-container {
            width: 50%;
            max-width: 600px;
            background: linear-gradient(135deg, #ff9a9e, #fad0c4);
            border: 3px solid #ff6f61;
            border-radius: 15px;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .chat-header {
            background: linear-gradient(135deg, #ff6f61, #ff9a9e);
            color: #fff;
            padding: 15px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chat-header i {
            margin-right: 10px;
            font-size: 20px;
        }

        .chat-box {
            max-height: 400px;
            overflow-y: auto;
            padding: 15px;
            background: linear-gradient(135deg, #fad0c4, #f3f9ff);
        }

        /* Message bubbles */
        .message {
            margin: 10px 0;
            display: flex;
        }
        .message.user {
            justify-content: flex-end;
        }
        .message.user .content {
            background: linear-gradient(135deg, #ff758c, #ff7eb3);
            color: #fff;
            text-align: right;
            border-radius: 20px 0 20px 20px;
        }
        .message.bot {
            justify-content: flex-start;
        }
        .message.bot .content {
            background: linear-gradient(135deg, #8e9eab, #eef2f3);
            color: #000;
            border-radius: 0 20px 20px 20px;
        }

        .message .content {
            padding: 10px 15px;
            max-width: 75%;
            font-size: 14px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* Input box */
        .input-box {
            display: flex;
            border-top: 2px solid #ff6f61;
            padding: 10px;
            background-color: #fff;
        }
        .input-box input {
            flex-grow: 1;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 20px;
            outline: none;
            transition: box

            transition: box-shadow 0.3s;
        }
        .input-box input:focus {
            box-shadow: 0 0 5px #ff6f61;
        }
        .input-box button {
            margin-left: 10px;
            padding: 10px 20px;
            font-size: 16px;
            background: linear-gradient(135deg, #ff758c, #ff7eb3);
            color: #fff;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            transition: background-color 0.3s ease-in-out;
        }
        .input-box button:hover {
            background: linear-gradient(135deg, #ff7eb3, #ff758c);
        }

        /* Smooth scroll for chatbox */
        .chat-box::-webkit-scrollbar {
            width: 8px;
        }
        .chat-box::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #ff758c, #ff7eb3);
            border-radius: 8px;
        }
        .chat-box::-webkit-scrollbar-track {
            background-color: #f1f1f1;
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <i class="fas fa-robot"></i> Welcome to Your Chatbot
        </div>
        <div class="chat-box" id="chatBox">
            <!-- Default chatbot message -->
            <div class="message bot">
                <div class="content">
                    How can i help you?
                </div>
            </div>
        </div>
        <div class="input-box">
            <input type="text" id="userInput" placeholder="Type your message...">
            <button onclick="sendMessage()">Send</button>
        </div>
    </div>

    <script>
        const chatBox = document.getElementById("chatBox");

        function sendMessage() {
            const userInput = document.getElementById("userInput");
            const message = userInput.value.trim();
            
            if (!message) return;

            // Append user message to chatbox
            appendMessage("user", message);

            // Get user email and user type (you may need to adjust how you retrieve these)
            const userEmail = 'patient@edoc.com'; // Replace with actual user email
            const userType = 'p'; // Replace with actual user type

            // Send message to PHP backend
            fetch("chat.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: new URLSearchParams({ 
                    message, 
                    email: userEmail, 
                    usertype: userType 
                }),
            })
            .then(response => response.text())
            .then(text => {
                // Append chatbot reply
                appendMessage("bot", text);
            })
            .catch(error => {
                appendMessage("bot", "Sorry, something went wrong.");
                console.error(error);
            });

            // Clear the input field
            userInput.value = "";
        }

        function appendMessage(sender, message) {
            const messageDiv = document.createElement("div");
            messageDiv.classList.add("message", sender);

            const contentDiv = document.createElement("div");
            contentDiv.classList.add("content");
            contentDiv.textContent = message;

            messageDiv.appendChild(contentDiv);
            chatBox.appendChild(messageDiv);

            // Scroll to the bottom
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    </script>
</body>
</html>