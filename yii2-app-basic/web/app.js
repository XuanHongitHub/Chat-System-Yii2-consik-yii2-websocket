// Lấy Csrf Token


function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}

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
    updateChatId(id, isRoom);

    if (isRoom) {
        $.ajax({
            url: '/chat/messages/' + id,
            method: 'GET',
            success: function (data) {
                updateChatUI(data, true);
            },
            error: function () {
                console.log('Error loading room messages');
            }
        });
    } else {
        $.ajax({
            url: '/chat/messages/' + id,
            method: 'GET',
            success: function (data) {
                updateChatUI(data);
            },
            error: function () {
                console.log('Error loading contact messages');
            }
        });
    }
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

$(document).ready(function () {
    Pusher.logToConsole = true;

    var pusher = new Pusher('9417daa5964067a88896', {
        cluster: 'ap1',
        forceTLS: true
    });

    var channel = pusher.subscribe('chat-channel');
    channel.bind('new-message', async function (data) {
        console.log("Current User ID:", currentUserId);

        // Lấy senderId từ chatId bằng cách gọi getSenderId
        const senderId = await getSenderId(data.chatId); // Sử dụng await để đợi
        console.log("Sender ID:", senderId); // Sử dụng senderId từ dữ liệu

        var newMessage;
        var lastSenderId = null;
        if (senderId == currentUserId) {
            // Tin nhắn của người dùng hiện tại
            if (senderId !== lastSenderId) {
                newMessage = `
                    <div class="message">
                        <div class="response">
                            <p class="text">${data.message} </p>
                        </div>
                    </div>
                    <p class="response-time time">Just now</p>
                `;
                lastSenderId = senderId;
            } else {
                newMessage = `
                    <div class="message text-only">
                        <div class="response">
                            <p class="text">${data.message} </p>
                        </div>
                    </div>
                    <p class="response-time time">Just now</p>
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
                            <p class="text">${data.message}</p>
                    </div>
                    <p class="response-time time">Just now</p>
                `;
                lastSenderId = senderId;
            } else {
                newMessage = `
                    <div class="message">
                            <p class="text">${data.message}</p>
                    </div>
                    <p class="response-time time">Just now</p>
                `;
            }
        }

        $('#messagesChat').append(newMessage);
        messagesChat.scrollTop = messagesChat.scrollHeight;
    });

    $('#sendMessageButton').on('click', function () {
        const chatId = document.getElementById('currentChatId').value;
        const message = document.getElementById('messageInput').value;
        const isRoom = document.getElementById('isRoom').value === "true";

        if (!chatId || !message) {
            console.error('Chat ID và nội dung tin nhắn không được để trống.');
            return;
        }

        $.ajax({
            url: 'chat/send-message',
            method: 'POST',
            headers: {
                'X-CSRF-Token': getCsrfToken()
            },
            data: {
                chatId: chatId,
                message: message,
                isRoom: isRoom,
            },
            success: function (response) {
                if (response.success) {
                    document.getElementById('messageInput').value = '';
                } else {
                    console.error('Error sending message:', response.errors);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
            },
        });
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

// function fetchNewMessages() {
//     const chatId = document.getElementById('currentChatId').value;
//     const isRoom = document.getElementById('isRoom').value === "true";

//     if (chatId && chatId.trim() !== "") {
//         $.ajax({
//             url: `/chat/messages/${chatId}`,
//             method: 'GET',
//             success: function (data) {
//                 updateChatUI(data, isRoom);
//             },
//             error: function () {
//                 console.log('Error loading new messages');
//             }
//         });
//     }
// }

// setInterval(fetchNewMessages, 2000);

