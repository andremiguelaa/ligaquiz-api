<?php

$config = \Config::get('app');
$app_name = $config['name'];
$url = $config['spa_url'];

return [
    'message_subject' => '['.$app_name.'] Suggestion/Complain',
    'reminder_subject' => '['.$app_name.'] Quiz available',
    'invitation_subject' => '['.$app_name.'] Invitation',
    'new_user_subject' => '['.$app_name.'] New user',
    'quiz_submission_subject' => '['.$app_name.'] Quiz submission',
    'new_special_quiz_proposal_subject' => '['.$app_name.'] New special quiz proposal',
    'hello' => 'Hi',
    'quiz_available' => 'Today\'s quiz is already available!',
    'quiz_available_deadline' => 'Don\'t forget to play today\'s quiz!',
    'quiz_opponent' => 'Your opponent',
    'quiz_link' => 'Click <a href="'.$url.'/quiz">here</a> to play.',
    'specialquiz_available' => 'Special quiz available today!',
    'specialquiz_available_deadline' => 'Don\'t forget to play today\'s special quiz!',
    'specialquiz_subject' => 'Title',
    'specialquiz_author' => 'Author',
    'specialquiz_link' => 'Click <a href="'.$url.'/special-quiz">here</a> to play.',
    'deadline' => 'You have until midnight to submit your responses.',
    'from' => 'From',
    'rights' => 'All rights reserved.',
    'invite_message' => 'You have been invited by a current player (:name) to play the '.$app_name.'.<br>If you want to accept the invitation, sign up <a href="'.$url.'/register/" target="_blank">here</a>.',
    'quiz_submission_copy' => 'This is a copy of your quiz submission.',
    'quiz_question' => 'Question',
    'correct_answer' => 'Correct answer',
    'your_answer' => 'Your answer'
];
