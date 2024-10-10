function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}
document.getElementById('messageInput').addEventListener('keydown', function (event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        document.getElementById('sendMessageButton').click();
    }
});
// Tìm Users
$(document).ready(function () {
    $('#contactUsername').on('input', function () {
        let username = $(this).val();
        if (username.length >= 1) {
            $.ajax({
                url: 'chat/search-user',
                method: 'GET',
                data: { username: username },
                success: function (data) {
                    let suggestions = $('#userSuggestions');
                    suggestions.empty();
                    if (data.length) {
                        suggestions.show();
                        data.forEach(function (user) {
                            suggestions.append(`
                               <div class="friend-item d-flex align-items-center mb-2">
                                    <div class="avatar-container me-2">
                                        <img src="${user.avatar ? user.avatar : 'https://icons.veryicon.com/png/o/miscellaneous/common-icons-30/my-selected-5.png'}" class="avatar" alt="${user.username}">
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="friend-name" data-username="${user.username}">${user.username}</div>
                                    </div>
                                    <button class="btn btn-primary btn-sm add-to-contact" data-username="${user.username}" data-contact-id="${user.id}" ${user.isAdded ? 'disabled' : ''} type="button">
                                        <i class="fa-solid fa-user-plus"></i> ${user.isAdded ? 'Added' : 'Add'}
                                    </button>
                                </div>
                            `);
                        });
                    } else {
                        suggestions.hide();
                    }
                },
                error: function () {
                    console.error("Có lỗi xảy ra khi tìm kiếm người dùng.");
                }
            });
        } else {
            $('#userSuggestions').hide();
        }
    });
});


// Add Contacts
$(document).ready(function () {
    $('#userSuggestions').on('click', '.add-to-contact', function (event) {
        event.preventDefault();

        console.log('Nút đã được nhấn!');

        var contactUserId = $(this).data('contact-id');
        var username = $(this).data('username');
        var avatar = $(this).data('avatar');

        $.ajax({
            type: 'POST',
            url: '/chat/add-contact',
            headers: {
                'X-CSRF-Token': getCsrfToken()
            },
            data: {
                contact_user_id: contactUserId
            },
            success: function (response) {
                console.log('Response:', response);
                if (response.success) {
                    $('#response-message').html('<div class="alert alert-success">Đã thêm ' + username + ' vào danh bạ!</div>');

                    // Đóng modal sau khi thêm liên hệ thành công
                    $('#addContactModal').modal('hide');

                    // Tạo phần tử discussion mới với thông tin người dùng thực
                    var newDiscussion = `
                        <div class="discussion message-active">
                            <div class="photo" style="background-image: url(${avatar});">
                                <div class="online"></div>
                            </div>
                            <div class="desc-contact">
                                <p class="name">${username}</p>
                                <p class="message">No messages</p>
                            </div>
                            <div class="timer">Just now</div>
                        </div>
                    `;
                    // Thêm discussion vào danh sách
                    $('.room-list').prepend(newDiscussion);
                } else {
                    $('#response-message').html('<div class="alert alert-danger">Có lỗi xảy ra: ' + response.error + '</div>');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
                $('#response-message').html('<div class="alert alert-danger">Có lỗi xảy ra: ' + error + '</div>');
            }
        });
    });
});

// Get user [Tagify] ---
var tagify;

document.addEventListener('DOMContentLoaded', function () {
    var input = document.querySelector('#members');
    tagify = new Tagify(input);

    fetch('/chat/get-contacts')
        .then(response => response.json())
        .then(data => {
            if (data && Array.isArray(data)) {
                const tags = data.map(contact => ({
                    value: contact.username,
                    id: contact.id
                }));
                tagify.addTags(tags);
            } else {
                console.error('Data format is not correct:', data);
            }
        })
        .catch(error => console.error('Error fetching contacts:', error));
});

// Add Chat Rooms
$(document).ready(function () {
    $('#addRoomButton').on('click', function (event) {
        event.preventDefault();

        var roomName = $('#roomName').val();
        var members = tagify.value;

        var memberData = members.map(function (member) {
            return { id: member.id };
        });

        $.ajax({
            type: 'POST',
            url: '/chat/add-room',
            headers: {
                'X-CSRF-Token': getCsrfToken()
            },
            data: {
                room_name: roomName,
                members: JSON.stringify(memberData)
            },
            success: function (response) {
                if (response.status === 'success') {
                    $('#response-message').html('<div class="alert alert-success">' + response.message + '</div>');
                    setTimeout(function () {
                        $('#addRoomModal').modal('hide');
                    }, 5000);
                } else {
                    $('#response-message').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function (xhr, status, error) {
                $('#response-message').html('<div class="alert alert-danger">Có lỗi xảy ra: ' + error + '</div>');
            }
        });
    });
});

// Active Contact Chat
function openChat(id, isRoom = false) {
    // Cập nhật id của chat hiện tại và kiểm tra loại chat (phòng hay contact)
    updateChatId(id, isRoom);

    // Cập nhật tên contact hoặc phòng trong phần tử #chatTitle
    let name = isRoom ? document.querySelector(`.discussion[data-room-id="${id}"] .name`).innerText :
        document.querySelector(`.discussion[data-contact-id="${id}"] .name`).innerText;

    if (name) {
        document.querySelector('#chatTitle').innerText = name;
    } else {
        document.querySelector('#chatTitle').innerText = 'Không có tên';
    }

    // Gửi yêu cầu lấy tin nhắn
    const url = isRoom ? '/chat/messages/' + id : '/chat/messages/' + id;
    $.ajax({
        url: url,
        method: 'GET',
        success: function (data) {
            updateChatUI(data, isRoom);
        },
        error: function () {
            console.log('Lỗi tải tin nhắn');
        }
    });
}


// Cập nhật chatId + Check isRoom
function updateChatId(chatId, isRoom) {
    document.getElementById('currentChatId').value = chatId;
    document.getElementById('isRoom').value = isRoom;
}

// Hiển thị Messages
function updateChatUI(data, isRoom = false) {
    var messages = data.messages;
    var messagesHtml = '';
    var lastSenderId = null;

    messages.forEach(function (message) {
        if (message.contactId === data.currentContactId) {
            if (!message.isMine) {
                if (message.user.id !== lastSenderId) {
                    messagesHtml = '<div class="message">' +
                        '<div class="photo" style="background-image: url(' + message.user.avatar + ');">' +
                        '<div class="online"></div></div>' +
                        '<p class="text">' + message.content + '</p>' +
                        '</div>' +
                        // '<p class="time">' + message.created_at + '</p>' + 
                        messagesHtml;
                    lastSenderId = message.user.id;
                } else {
                    messagesHtml = '<div class="message text-only">' +
                        '<p class="text">' + message.content + '</p>' +
                        '</div>' + messagesHtml;
                }
            } else {
                // Tin nhắn của người nhận
                if (message.user.id !== lastSenderId) {
                    messagesHtml = '<div class="message text-only">' +
                        '<div class="response">' +
                        '<p class="text">' + message.content + '</p>' +
                        '</div></div>' +
                        // '<p class="response-time time">' + message.created_at + '</p>' + 
                        messagesHtml;
                    lastSenderId = message.user.id;
                } else {
                    messagesHtml = '<div class="message text-only">' +
                        '<div class="response">' +
                        '<p class="text">' + message.content + '</p>' +
                        '</div></div>' + messagesHtml;
                }
            }
        }
    });

    $('#messagesChat').html(messagesHtml);

    var messagesChat = document.getElementById('messagesChat');
    messagesChat.scrollTop = messagesChat.scrollHeight;

    var chatName = isRoom ? data.roomName : data.contactName;
    $('.header-chat .name').text(chatName);
}
let socket;
$(document).ready(function () {


    socket = new WebSocket('ws://localhost:3000'); // Kết nối đến WebSocket server

    socket.onopen = function () {
        console.log('Kết nối đến WebSocket server thành công.');
    };

    socket.onmessage = function (event) {

        // console.log('Response:' + event.data);
        const data = JSON.parse(event.data); // Giả sử dữ liệu gửi qua là JSON
        const senderId = currentUserId; // Lấy ID người gửi từ dữ liệu
        const message = data.message;
        console.log("Sender ID:", senderId);
        console.log("Messages Content:", data.message);
        console.log("Current User ID:", currentUserId);

        var newMessage;
        var lastSenderId = null;
        if (senderId == currentUserId) {
            // Tin nhắn của người dùng hiện tại
            if (senderId !== lastSenderId) {
                newMessage = `
                    <div class="message">
                        <div class="response">
                            <p class="text">${message} </p>
                        </div>
                    </div>
                `;
                lastSenderId = senderId;
            } else {
                newMessage = `
                    <div class="message text-only">
                        <div class="response">
                            <p class="text">${message} </p>
                        </div>
                    </div>
                `;
            }
        } else {
            // Tin nhắn từ người khác
            if (senderId !== lastSenderId) {
                newMessage = `
                    <div class="message">
                        <div class="photo" style="background-image: url(${senderId.avatar ? senderId.avatar : 'https://icons.veryicon.com/png/o/miscellaneous/common-icons-30/my-selected-5.png'})" class="avatar" alt="${senderId.username}"></div>
                        <div class="online"></div>
                    </div>
                    <p class="text">${message}</p>
                    </div>
                `;
                lastSenderId = senderId;
            } else {
                newMessage = `
                    <div class="message">
                        <p class="text">${message}</p>
                    </div>
                `;
            }
        }

        $('#messagesChat').append(newMessage);
        messagesChat.scrollTop = messagesChat.scrollHeight;
    };

    $('#sendMessageButton').on('click', function () {
        const chatId = document.getElementById('currentChatId').value;
        const message = document.getElementById('messageInput').value;
        const isRoom = document.getElementById('isRoom').value === "true";

        if (!chatId || !message) {
            console.error('Chat ID và nội dung tin nhắn không được để trống.');
            return;
        }

        // let data = JSON.stringify({
        //     chatId: chatId,
        //     message: message,
        //     isRoom: isRoom,
        // });

        socket.send(JSON.stringify({
            chatId: chatId,
            message: message,
            isRoom: isRoom,
        }));

        // Xóa Tin nhắn trong input
        document.getElementById('messageInput').value = '';

    });
});


function getSenderId(chatId) {
    return fetch(`/chat/get-sender-id?chatId=${chatId}`)
        .then(response => response.json())
        .then(data => {
            if (data.user_id) {
                console.log("User ID:", data.user_id);
                return data.user_id; // Trả về user_id
            } else {
                console.error(data.error); // Xử lý lỗi
                return null; // Hoặc bạn có thể throw một lỗi
            }
        })
        .catch(error => {
            console.error('Error:', error);
            return null; // Hoặc bạn có thể throw một lỗi
        });
}




$(function () {
    var chat = new WebSocket("ws://localhost:3000");
    chat.onmessage = function (e) {
        var response = JSON.parse(e.data);
        if (response.type && response.type == "chat") {
            if (response.from == "' . Yii::$app->user->identity->username . '") {
                $("#chat").append("<div class=\"direct-chat-msg\"><div class=\"direct-chat-infos clearfix\"><span class=\"direct-chat-name float-left\">" + response.from + " </span><span class=\"direct-chat-timestamp float-right\">" + response.date + "</span></div><i class=\"direct-chat-img fas fa-user-circle\" style=\"font-size:40px\"></i><div class=\"direct-chat-text\">" + response.message + "</div></div>");
            } else {
                $("#chat").append("<div class=\"direct-chat-msg right\"><div class=\"direct-chat-infos clearfix\"><span class=\"direct-chat-name float-right\">" + response.from + " </span><span class=\"direct-chat-timestamp float-left\">" + response.date + "</span></div><i class=\"direct-chat-img fas fa-user-circle\" style=\"font-size:40px\"></i><div class=\"direct-chat-text\">" + response.message + "</div></div>");
            }
        } else if (response.message) {
            console.log(response.message);
        }
    };
    chat.onopen = function (e) {
        console.log("Connection established! Please, set your username.");
        chat.send(JSON.stringify({ "action": "setName", "name": "' . Yii::$app->user->identity->username . '" }));
    };
    $("#btnSend").click(function () {
        if ($("#message").val()) {
            chat.send(JSON.stringify({ "action": "chat", "message": $("#message").val() }));
            $("#message").val("");
            console.log(chat);
        } else {
            alert("' . Yii::t('app', 'Enter the message') . '");
        }
    });
})