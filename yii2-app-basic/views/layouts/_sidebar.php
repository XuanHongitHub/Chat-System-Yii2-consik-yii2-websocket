<?php

use yii\bootstrap5\Nav;
use common\widgets\Alert;
use frontend\assets\AppAsset;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;
use yii\bootstrap5\NavBar;
use yii\helpers\Url;

?>
<nav class="menu">
    <ul class="items">
        <li class="item item-active">
            <i class="fas fa-comments" aria-hidden="true"></i>
        </li>
        <li class="item">
            <a href="<?= Url::toRoute(['site/login']) ?>"><i class="fas fa-user text-white" aria-hidden="true"></i></a>
        </li>
        <li class="item">
            <?= Html::beginForm(['site/logout'], 'post') ?>
            <a href="#" onclick="this.closest('form').submit(); return false;">
                <i class="fa-solid fa-right-from-bracket text-white" aria-hidden="true"></i>
            </a>
            <?= Html::endForm() ?>
        </li>
    </ul>
</nav>

<section class="discussions">
    <div class="user-list">
        <div class="discussion search">
            <div class="searchbar">
                <i class="fa fa-search" aria-hidden="true"></i>
                <input type="text" placeholder="Tìm kiếm..."></input>
            </div>
        </div>
        <div class="header-title">
            <span>Contacts</span>
            <div icon="outline-add-new-contact-2" data-bs-toggle="modal" data-bs-target="#addContactModal"
                class="z--btn--v2 btn-tertiary-neutral medium --rounded icon-only" title="Thêm bạn">
                <i class="fa fa-plus pre"></i>
            </div>
        </div>
        <div id="addContactModal" tabindex="-1" aria-labelledby="addContactModalLabel" aria-hidden="true"
            class="modal fade">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="addContactModalLabel">Add Contact</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="addContactForm">
                            <div class="form-group">
                                <label for="contactUsername">Username <span class="text-danger">*</span></label>
                                <input type="text" id="contactUsername" class="form-control" placeholder="Username"
                                    required autocomplete="off">
                                <div id="userSuggestions" class="list-group mt-2" style="display: none;"></div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <?php foreach ($contacts as $contact): ?>
            <div class="discussion <?= $contact['id'] == $activeContactId ? 'message-active' : '' ?>"
                data-contact-id="<?= $contact['id'] ?>" data-recipient-id="<?= $contact['recipientId'] ?>"
                data-related-id="<?= $contact['relatedId'] ?>"
                onclick="openChat(<?= $contact['id'] ?>, <?= $contact['recipientId'] ?>, <?= $contact['relatedId'] ?> )">
                <div class="photo" style="background-image: url(<?= Html::encode($contact['avatarUrl']) ?>);">
                    <div class="online"></div>
                </div>
                <di v class="desc-contact">
                    <p class="name"><?= Html::encode($contact['username']) ?></p>
                    <p class="message"><?= Html::encode($contact['lastMessageContent']) ?></p>
                </di>
                <div class="timer">
                    <?= Html::encode($contact['relativeTime']) ?>
                </div>
                <div class="ms-auto me-3 dropdown">
                    <button class="btn border-0 toggle" type="button" id="dropdownMenuButton<?= $contact['id'] ?>"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa fa-ellipsis-v" aria-hidden="true"></i>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton<?= $contact['id'] ?>">
                        <li><a class="dropdown-item delete-contact" href="#">Remove Contact</a></li>
                    </ul>
                </div>
            </div>
        <?php endforeach; ?>

    </div>
    <div class="room-list">
        <div class="header-title">
            <span>Rooms</span>
            <div icon="outline-add-new-contact-2" data-bs-toggle="modal" data-bs-target="#searchRoomModal"
                title="Tìm phòng">
                <i class="fa-solid fa-magnifying-glass"></i>
            </div>
            <div class="ms-2" icon="outline-add-new-contact-2" data-bs-toggle="modal" data-bs-target="#addRoomModal"
                title="Thêm phòng">
                <i class="fa fa-plus pre"></i>
            </div>
        </div>

        <!-- Modal Thêm Phòng -->
        <div id="addRoomModal" tabindex="-1" aria-labelledby="addRoomModalLabel" aria-hidden="true" class="modal fade">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="addRoomModalLabel">Create Room</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="card-body">
                            <div class="mb-4" id="response-message"></div>

                            <div class="form-group mb-4">
                                <label for="roomName" class="fw-medium">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="roomName" id="roomName"
                                    placeholder="Enter room name">
                            </div>

                            <div class="form-group mb-4">
                                <label for="members" class="fw-medium">Members <span
                                        class="text-danger">*</span></label>
                                <input name="members" placeholder="Enter members" class="form-control mb-3"
                                    id="members">
                                <div id="memberSuggestions" class="list-group" style="display:none;"></div>
                            </div>

                            <div class="form-group mb-4">
                                <label for="visibility" class="fw-medium">Visibility <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" name="visibility" id="visibility">
                                    <option value="0" selected>Private</option>
                                    <option value="1">Public</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" id="addRoomButton" class="btn btn-primary">Thêm phòng</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal Tìm Phòng -->
        <div id="searchRoomModal" tabindex="-1" aria-labelledby="searchRoomModalLabel" aria-hidden="true"
            class="modal fade">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="searchRoomModalLabel">Search Room</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group mb-4">
                            <label for="searchRoomName" class="fw-medium">Room name</label>
                            <input type="text" class="form-control" name="searchRoomName" id="searchRoomName"
                                placeholder="Room name ...">
                        </div>

                        <div class="list-group" id="roomResults"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Danh sách phòng -->
        <?php foreach ($roomData as $room): ?>
            <?php if (isset($room['id'])): ?>
                <div class="discussion" data-room-id="<?= Html::encode($room['id']) ?>"
                    onclick="openChat(<?= Html::encode($room['id']) ?>, null, null, true)">
                    <div class="avatar-group">
                        <?php foreach ($room['avatars'] as $avatar): ?>
                            <img src="<?= $avatar ?>" alt="Avatar" class="avatar-img">
                        <?php endforeach; ?>
                    </div>
                    <div class="desc-contact">
                        <p class="name"><?= Html::encode($room['name']) ?></p>
                        <p class="message"><?= Html::encode($room['lastMessageContent']) ?></p>
                    </div>
                    <div class="timer"><?= Html::encode($room['relativeTime']) ?></div>

                    <div class="ms-auto me-3 dropdown">
                        <button class="btn border-0 toggle addMemberModalTrigger" data-room-id="<?= $room['id'] ?>"
                            data-bs-toggle="modal" data-bs-target="#addMemberModal<?= $room['id'] ?>" type="button"
                            aria-expanded="false">
                            <i class="fa fa-ellipsis-v" aria-hidden="true"></i>
                        </button>

                        <!-- Modal Thêm Thành Viên -->
                        <div class="modal fade" id="addMemberModal<?= $room['id'] ?>" tabindex="-1"
                            aria-labelledby="addMemberModalLabel<?= $room['id'] ?>" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="addMemberModalLabel<?= $room['id'] ?>">Room Details</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-6">
                                                <div id="currentMembers<?= $room['id'] ?>" class="mt-3">
                                                    <h6>List Members:</h6>
                                                    <ul class="list-group" id="membersList<?= $room['id'] ?>"></ul>
                                                </div>
                                            </div>
                                            <div class="col-6 add-members mt-3">
                                                <h6>Search Users:</h6>
                                                <input type="text" id="members<?= $room['id'] ?>" class="form-control"
                                                    placeholder="Search members...">
                                                <div id="memberSuggestions<?= $room['id'] ?>" class="mt-2"
                                                    style="display: none;"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-danger me-auto" data-bs-dismiss="modal"
                                            onclick="leaveChatRoom(<?= $room['id'] ?>)">
                                            <i class="fa-solid fa-right-from-bracket me-2"></i>Leave Chat Room
                                        </button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="button" class="btn btn-primary"
                                            onclick="addMember(<?= $room['id'] ?>)">Add Member</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</section>
<?php

?>