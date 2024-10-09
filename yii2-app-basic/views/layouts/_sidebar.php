<?php

use yii\bootstrap5\Nav;
use common\widgets\Alert;
use frontend\assets\AppAsset;
use yii\bootstrap5\Breadcrumbs;
use yii\bootstrap5\Html;
use yii\bootstrap5\NavBar;

?>

<nav class="menu">
    <ul class="items">
        <li class="item">
            <i class="fa-solid fa-house"></i>
        </li>
        <li class="item">
            <i class="fas fa-user" aria-hidden="true"></i>
        </li>
        <li class="item">
            <i class="fas fa-pencil-alt" aria-hidden="true"></i>
        </li>
        <li class="item item-active">
            <i class="fas fa-comments" aria-hidden="true"></i>
        </li>
        <li class="item">
            <i class="fas fa-file-alt" aria-hidden="true"></i>
        </li>
        <li class="item">
            <i class="fas fa-cog" aria-hidden="true"></i>
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
            <span>Contact</span>
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
                data-contact-id="<?= $contact['id'] ?>" onclick="openChat(<?= $contact['id'] ?>)">
                <div class="photo" style="background-image: url(<?= Html::encode($contact['avatarUrl']) ?>);">
                    <div class="online"></div>
                </div>
                <div class="desc-contact">
                    <p class="name"><?= Html::encode($contact['username']) ?></p>
                    <p class="message"><?= Html::encode($contact['lastMessageContent']) ?></p>
                </div>
                <div class="timer">
                    <?= Html::encode($contact['relativeTime']) ?>
                </div>
            </div>
        <?php endforeach; ?>

    </div>
    <div class="room-list">
        <div class="header-title">
            <span>Rooms</span>
            <div icon="outline-add-new-contact-2" data-bs-toggle="modal" data-bs-target="#addRoomModal" class=""
                title="Thêm bạn">
                <i class="fa fa-plus pre"></i>
            </div>
        </div>
        <!-- Modal -->
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
                                <input name="members" placeholder="Enter members" class="form-control" id="members">
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
        <?php foreach ($roomData as $room): ?>
            <?php if (isset($room['id'])): ?>
                <div class="discussion" data-room-id="<?= Html::encode($room['id']) ?>"
                    onclick="openChat(<?= Html::encode($room['id']) ?>, true)">
                    <div class="photo" style="background-image: url(<?= Html::encode($room['avatarUrl']) ?>);">
                    </div>
                    <div class="desc-contact">
                        <p class="name"><?= Html::encode($room['name']) ?></p>
                        <p class="message"><?= Html::encode($room['lastMessageContent']) ?></p>
                    </div>
                    <div class="timer">
                        <?= Html::encode($room['relativeTime']) ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</section>


<?php

?>