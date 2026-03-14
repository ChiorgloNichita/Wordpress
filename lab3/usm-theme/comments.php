<?php

if (post_password_required())
  return;

?>

<div class="comments">

  <h3>Комментарии</h3>

  <?php
  if (have_comments()) {
    wp_list_comments();
  }

  comment_form();
  ?>

</div>
