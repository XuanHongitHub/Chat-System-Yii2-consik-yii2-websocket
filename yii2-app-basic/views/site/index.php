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
        <p class="name">Megan Leib</p>
        <i class="icon clickable fa fa-ellipsis-h right" aria-hidden="true"></i>
    </div>

    <div class="messages-chat" id="messagesChat">
    </div>

    <div class="footer-chat">
        <input type="hidden" id="currentChatId" value="">
        <input type="hidden" id="isRoom" value="">
        <i class="icon fa fa-smile-o clickable" style="font-size:25pt;" aria-hidden="true"></i>
        <textarea class="write-message" id="messageInput" placeholder="Type your message here"></textarea>
        <i class="icon send fa fa-paper-plane-o clickable" id="sendMessageButton" aria-hidden="true"></i>
    </div>
</section>
<script>
document.getElementById('messageInput').addEventListener('keydown', function(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        document.getElementById('sendMessageButton').click();
    }
});
</script>
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>