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
                                    <button class="btn btn-primary btn-sm add-to-contact" 
                                            data-username="${user.username}" 
                                            data-avatar="${user.avatar}" 
                                            data-contact-id="${user.id}" 
                                            data-recipient-id="${user.recipientId}" 
                                            data-related-id="${user.relatedId}" 
                                            ${user.isAdded ? 'disabled' : ''} 
                                            onclick="addContact(${user.id})" 
                                            type="button">
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

        var contactId = $(this).data('contact-id');
        var username = $(this).data('username');
        // var avatar = $(this).data('avatar');
        // var relatedId = $(this).data('relatedId');
        // var recipientId = $(this).data('id');
        $.ajax({
            type: 'POST',
            url: '/chat/add-contact',
            headers: {
                'X-CSRF-Token': getCsrfToken()
            },
            data: {
                contact_user_id: contactId
            },
            success: function (response) {
                console.log('Response:', response);
                if (response.success) {
                    $('#response-message').html('<div class="alert alert-success">Đã thêm ' + username + ' vào danh bạ!</div>');

                    $('#addContactModal').modal('hide');

                    // var newDiscussion = `
                    //     <div class="discussion message-active" data-contact-id="${contactId}" data-recipient-id="${recipientId}" data-related-id="${relatedId}" onclick="openChat(${contactId}, ${recipientId}, ${relatedId} )">
                    //         <div class="photo" style="background-image: url(${avatar});">
                    //             <div class="online"></div>
                    //         </div>
                    //         <div class="desc-contact">
                    //             <p class="name">${username}</p>
                    //             <p class="message">No messages</p>
                    //         </div>
                    //         <div class="timer">Just now</div>
                    //     </div>
                    // `;
                    // $('.user-list').prepend(newDiscussion);
                    fetchContacts();

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
// Add Chat Rooms
$(document).ready(function () {
    $('#members').on('input', function () {
        let query = $(this).val();
        if (query.length >= 1) {
            $.ajax({
                url: '/chat/search-user',
                method: 'GET',
                data: { username: query },
                success: function (data) {
                    let suggestions = $('#memberSuggestions');
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
                                    <input type="checkbox" class="select-member ms-2" data-username="${user.username}" data-user-id="${user.id}">
                                </div>
                            `);
                        });
                    } else {
                        suggestions.hide();
                    }
                },
                error: function () {
                    console.error("Error searching users.");
                }
            });
        } else {
            $('#memberSuggestions').hide();
        }
    });

    $('#addRoomButton').on('click', function (event) {
        event.preventDefault();
        var roomName = $('#roomName').val();
        var selectedMembers = [];

        // Collect selected members from checked checkboxes
        $('#memberSuggestions .select-member:checked').each(function () {
            let userId = $(this).data('user-id');
            let username = $(this).data('username');
            selectedMembers.push({ id: userId, username: username });
        });

        $.ajax({
            type: 'POST',
            url: '/chat/add-room',
            headers: {
                'X-CSRF-Token': getCsrfToken()
            },
            data: {
                room_name: roomName,
                members: JSON.stringify(selectedMembers)
            },
            success: function (response) {
                fetchRooms();
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

$(document).ready(function () {
    $('#searchRoomName').on('input', function () {
        var query = $(this).val();
        if (query.length > 1) {
            $.ajax({
                url: '/chat/search-room',
                type: 'GET',
                headers: {
                    'X-CSRF-Token': getCsrfToken()
                },
                data: { roomName: query },
                success: function (data) {
                    var roomList = $('#roomResults');
                    roomList.empty();

                    if (data.length > 0) {
                        data.forEach(function (room) {
                            if (room.visibility === '1') {
                                roomList.append('<div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">' +
                                    '<span><strong>' + room.name + '</strong><br>' +
                                    '<span>Visibility: ' + (room.visibility === '1' ? 'Public' : 'Private') + '</span></span>' +
                                    '<button class="btn btn-primary btn-sm" onclick="joinRoom(' + room.id + ')">Join Room</button>' +
                                    '</div>');
                            }
                        });
                    } else {
                        roomList.append('<p class="text-muted">Không tìm thấy phòng phù hợp</p>');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error occurred:', error);
                }
            });
        } else {
            $('#roomResults').empty();
        }
    });
});

function joinRoom(roomId) {
    $.ajax({
        url: '/chat/join-room', // URL đến action tham gia phòng
        type: 'POST',
        headers: {
            'X-CSRF-Token': getCsrfToken()
        },
        data: { roomId: roomId },
        success: function (response) {
            fetchRooms();
            if (response.status === 'success') {
                alert(response.message);
            } else {
                alert(response.message);
            }
        },
        error: function (xhr, status, error) {
            console.error('Error occurred:', error);
        }
    });
}


// Active Contact Chat
function openChat(id, recipientId = null, relatedId = null, isRoom = false) {
    updateChatId(id, recipientId, relatedId, isRoom);

    let name = isRoom ? document.querySelector(`.discussion[data-room-id="${id}"] .name`).innerText :
        document.querySelector(`.discussion[data-contact-id="${id}"] .name`).innerText;


    // Kiểm tra xem name có hợp lệ không
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
function updateChatId(chatId, recipientId, relatedId, isRoom) {
    document.getElementById('currentChatId').value = chatId;
    document.getElementById('isRoom').value = isRoom;
    document.getElementById('recipientId').value = recipientId;
    document.getElementById('relatedId').value = relatedId;
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

// Làm mới Contacts
function fetchContacts() {
    $.ajax({
        url: 'site/index',
        type: 'GET',
        success: function (data) {
            $('.user-list').html($(data).find('.user-list').html());
        },
        error: function () {
            alert('Có lỗi xảy ra khi tải danh sách liên hệ.');
        }
    });
}

// Làm mới Chat Rooms
function fetchRooms() {
    $.ajax({
        url: 'site/index',
        type: 'GET',
        success: function (data) {
            $('.room-list').html($(data).find('.room-list').html());
        },
        error: function () {
            alert('Có lỗi xảy ra khi tải danh sách phòng chat.');
        }
    });
}

// Search + Add Members
$(document).ready(function () {
    // Tìm kiếm thành viên
    $('[id^=members]').on('input', function () {
        let roomId = $(this).attr('id').replace('members', ''); // Lấy ID phòng từ input
        let query = $(this).val();

        if (query.length >= 1) {
            $.ajax({
                url: '/chat/search-user',
                method: 'GET',
                data: { username: query, room_id: roomId }, // Thêm room_id vào dữ liệu
                success: function (data) {
                    let suggestions = $('#memberSuggestions' + roomId);
                    suggestions.empty();
                    if (data.length) {
                        suggestions.show();
                        data.forEach(function (user) {
                            if (!user.isMember) {
                                suggestions.append(`
                                    <div class="friend-item d-flex align-items-center mb-2">
                                        <div class="avatar-container me-2">
                                            <img src="${user.avatar ? user.avatar : 'https://icons.veryicon.com/png/o/miscellaneous/common-icons-30/my-selected-5.png'}" class="avatar" alt="${user.username}">
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="friend-name" data-username="${user.username}">${user.username}</div>
                                        </div>
                                        <input type="checkbox" class="select-member ms-2" data-username="${user.username}" data-user-id="${user.id}">
                                    </div>
                                `);
                            }
                        });
                    } else {
                        suggestions.hide();
                    }
                },
                error: function () {
                    console.error("Error searching users.");
                }
            });
        } else {
            $('#memberSuggestions' + roomId).hide();
        }
    });

    $('.addMemberModalTrigger').on('click', function () {
        let roomId = $(this).data('room-id');

        $.ajax({
            url: '/chat/get-chat-room-users/' + roomId,
            method: 'GET',
            success: function (response) {
                let membersList = $('#membersList' + roomId);
                membersList.empty();

                if (response.status === 'success') {
                    response.users.forEach(function (user) {
                        membersList.append(`
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                ${user.username}
                                <button class="btn btn-danger btn-sm delete-member rounded-5" data-user-id="${user.id}"><i class="fa-solid fa-user-xmark"></i></button>
                            </li>
                        `);
                    });
                } else {
                    membersList.append('<li class="list-group-item">Không có thành viên nào.</li>');
                }
            },
            error: function () {
                console.error("Error fetching chat room users.");
            }
        });
    });
});

// Add Members
function addMember(roomId) {
    let selectedMembers = [];
    $(`#memberSuggestions${roomId} .select-member:checked`).each(function () {
        selectedMembers.push({ id: $(this).data('user-id') });
    });

    if (selectedMembers.length > 0) {
        $.ajax({
            url: '/chat/add-member',
            method: 'POST',
            headers: {
                'X-CSRF-Token': getCsrfToken()
            },
            data: {
                members: JSON.stringify(selectedMembers),
                roomId: roomId,
            },
            success: function (response) {
                if (response.status === 'success') {
                    $('#addMemberModal' + roomId).modal('hide');
                } else {
                    console.error("Error adding members: " + response.message);
                }
            },
            error: function () {
                console.error("Error adding members.");
            }
        });
    } else {
        alert("Vui lòng chọn ít nhất một thành viên!");
    }
}

// Delete Members
$(document).on('click', '.delete-member', function () {
    let userId = $(this).data('user-id');
    let roomId = $(this).closest('ul').attr('id').replace('membersList', ''); // Sửa đoạn này

    $.ajax({
        url: '/chat/delete-member',
        method: 'POST',
        headers: {
            'X-CSRF-Token': getCsrfToken()
        },
        data: { id: userId, roomId: roomId },
        success: function (response) {
            if (response.status === 'success') {
                $(this).closest('.list-group-item').remove();
            } else {
                console.error("Error deleting member: " + response.message);
            }
        }.bind(this),
        error: function () {
            console.error("Error deleting member.");
        }
    });
});

// Leave Chat Room
function leaveChatRoom(roomId) {
    $.ajax({
        url: '/chat/leave-chat-room',
        method: 'POST',
        data: { roomId: roomId },
        headers: {
            'X-CSRF-Token': getCsrfToken()
        },
        success: function (response) {
            if (response.status === 'success') {
                fetchRooms();
            } else {
                console.error("Error leaving chat room: " + response.message);
                alert("Có lỗi xảy ra khi rời khỏi phòng chat.");
            }
        },
        error: function () {
            console.error("Error leaving chat room.");
            alert("Có lỗi xảy ra khi rời khỏi phòng chat.");
        }
    });
}
