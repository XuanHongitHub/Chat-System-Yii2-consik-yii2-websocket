<?php

/** @var yii\web\View $this */

use yii\helpers\Html;
use yii\helpers\Json;

$this->title = 'My Yii Application';
?>
<?php echo $this->render('/layouts/_sidebar', [
    'contacts' => $contacts,
    'activeContactId' => $activeContactId,
    'roomData' => $roomData,
]); ?>

<section class="chat">
    <div class="header-chat">
        <i class="icon fa fa-user-o" aria-hidden="true"></i>
        <p class="name" id="chatTitle"></p>
        <i class="icon clickable fa fa-ellipsis-h right" aria-hidden="true"></i>
    </div>

    <div class="messages-chat" id="messagesChat">

    </div>

    <div class="footer-chat">
        <input type="hidden" id="currentChatId" value="">
        <input type="hidden" id="isRoom" value="">
        <input type="hidden" id="recipientId" value="">
        <input type="hidden" id="relatedId" value="">
        <i class="icon fa fa-smile-o clickable" style="font-size:25pt;" aria-hidden="true"></i>
        <textarea class="write-message" id="messageInput" placeholder="Type your message here"></textarea>
        <i class="icon send fa-solid fa-paper-plane clickable" id="sendMessageButton" aria-hidden="true"></i>
    </div>
</section>

<script>
function getSenderId(userId) {
    return fetch(`/chat/get-sender-id?userId=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.user_id && data.username) {
                console.log("User ID:", data.user_id);
                console.log("Username:", data.username);
                return {
                    user_id: data.user_id,
                    username: data.username,
                    avatar: data.avatar
                };
            } else {
                console.error(data.error);
                return null;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            return null;
        });
}
</script>

<?php $this->registerJs('
$(document).ready(function() {

    var chat = new WebSocket("ws://localhost:3000");
  
    chat.onopen = function() {
        console.log("Connection established!.");
        chat.send(JSON.stringify({
            action: "authenticate",
            userId: "' . Yii::$app->user->identity->id . '"
        }));

        chat.send(JSON.stringify({
            action: "setName",
            name: "' . Yii::$app->user->identity->username . '"
        }));
       
    };

    chat.onmessage = function(e) {

        console.log("Received message:", e.data);
        var data = JSON.parse(e.data);
        var senderName = data.from;
        var message = data.message;
        var chatId = data.chatId;
        const currentChatId = document.getElementById("currentChatId").value;
        const senderId = document.getElementById("relatedId").value;
        console.log("Sender Name:", senderName);
        console.log("Message Content:", message);
        console.log("1. Chat ID:", data.chatId);
        console.log("2. Current Chat ID:", currentChatId);

        console.log("Recipient ID:", data.recipientId);
        // Only display the message if the chatId matches the current chat
        if (data.relatedId === currentChatId || data.chatId == currentChatId || data.relatedId === senderId) {
            var newMessage;
            var lastSenderId = null; // Reset last sender id for every message display

            // Kiểm tra xem tin nhắn đến từ người dùng hiện tại hay người khác
            var isCurrentUser = senderName === "' . Yii::$app->user->identity->username . '";

                if (isCurrentUser) {
                    newMessage = `
                    <div class="message">
                        <div class="response">
                            <p class="text">${message}</p> 
                        </div>
                    </div>
                    `;
                } else {
                    // Nếu đây là một phòng chat, sử dụng senderId
                    if (data.chatId == currentChatId) {
                        // Kiểm tra nếu là phòng chat
                        if (data.isRoom) {
                            console.log("User ID", data.userId);
                            getSenderId(data.userId).then(senderInfo => {
                                if (senderInfo) {
                                    var avatar = senderInfo.avatar;
                                    var username = senderInfo.username;

                                    newMessage = `
                                    <div class="message">
                                        <div class="photo" style="background-image: url(${avatar});" class="avatar" alt="${username}">
                                            <div class="online"></div>
                                        </div>
                                        <p class="text">${message}</p>
                                    </div>
                                    `;
                                } else {
                                    newMessage = `
                                    <div class="message">
                                        <p class="text">${message} (Người gửi không xác định)</p>
                                    </div>
                                    `;
                                }

                                // Thêm tin nhắn vào khung chat
                                $("#messagesChat").append(newMessage);
                                var messagesChat = document.getElementById("messagesChat");
                                messagesChat.scrollTop = messagesChat.scrollHeight;
                            });
                        }
                    } else {
                        // Nếu không phải là phòng chat, gọi getSenderId
                        if (!data.isRoom) {
                            getSenderId(data.userId).then(senderInfo => {
                            if (senderInfo) {
                                var avatar = senderInfo.avatar;
                                var username = senderInfo.username;

                                newMessage = `
                                <div class="message">
                                    <div class="photo" style="background-image: url(${avatar});" class="avatar" alt="${username}">
                                        <div class="online"></div>
                                    </div>
                                    <p class="text">${message}</p>
                                </div>
                                `;
                            } else {
                                newMessage = `
                                <div class="message">
                                    <p class="text">${message} (Người gửi không xác định)</p>
                                </div>
                                `;
                            }
                            
                            // Thêm tin nhắn vào khung chat
                            $("#messagesChat").append(newMessage);
                            var messagesChat = document.getElementById("messagesChat");
                            messagesChat.scrollTop = messagesChat.scrollHeight;

                            // Cập nhật tin nhắn cuối cùng trong sidebar
                            var sidebarLastMessage = data.chatId;
                            var sidebarMessage = document.querySelector(`.discussion[data-related-id="${sidebarLastMessage}"] .desc-contact .message`);
                            if (sidebarMessage) {
                                sidebarMessage.textContent = message; 
                            }
                        });
                        }
                    }
                }

                // Hiển thị tin nhắn sau khi đã được xử lý
                $("#messagesChat").append(newMessage);
                var messagesChat = document.getElementById("messagesChat");
                messagesChat.scrollTop = messagesChat.scrollHeight;

                var sidebarLastMessage = data.chatId;
                var sidebarMessage = document.querySelector(`.discussion[data-contact-id="${sidebarLastMessage}"] .desc-contact .message`);
                if (sidebarMessage) {
                    sidebarMessage.textContent = message; 
                }
                var sidebarRoomMessage = document.querySelector(`.discussion[data-room-id="${sidebarLastMessage}"] .desc-contact .message`);
                if (sidebarRoomMessage) {
                    sidebarRoomMessage.textContent = message; 
                }
            // Cập nhật lastSenderId sau khi tin nhắn được xử lý
            lastSenderId = senderName;
        }

    };

    // Sending messages
    $("#sendMessageButton").on("click", function() {
        const chatId = document.getElementById("currentChatId").value;
        const message = document.getElementById("messageInput").value;
        const isRoom = document.getElementById("isRoom").value === "true";
        const recipientId = document.getElementById("recipientId").value;
        const relatedId = document.getElementById("relatedId").value;

        if (!chatId || !message) {
            console.error("Chat ID và nội dung tin nhắn không được để trống.");
            return;
        }

        chat.send(JSON.stringify({
            action: "chat",
            chatId: chatId,
            message: message,
            isRoom: isRoom,
            userId: "' . Yii::$app->user->identity->id . '",
            recipientId: recipientId,
            relatedId: relatedId,
        }));

        // Clear the message input field
        document.getElementById("messageInput").value = "";
        console.log(isRoom);
        console.log(chat);


    });
});
', \yii\web\View::POS_END); ?>