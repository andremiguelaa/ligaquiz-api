<?php

$config = \Config::get('app');
$app_name = $config['name'];
$url = $config['spa_url'];

return [
    'message_subject' => '['.$app_name.'] Sugestão/Reclamação',
    'reminder_subject' => '['.$app_name.'] Quiz disponível',
    'invitation_subject' => '['.$app_name.'] Convite',
    'new_user_subject' => '['.$app_name.'] Novo utilizador',
    'quiz_submission_subject' => '['.$app_name.'] Quiz submetido',
    'new_special_quiz_proposal_subject' => '['.$app_name.'] Nova proposta de quiz especial',
    'hello' => 'Olá',
    'quiz_available' => 'Já está disponível o quiz de hoje!',
    'quiz_available_deadline' => 'Não te esqueças de responder ao quiz de hoje!',
    'quiz_opponent' => 'O teu adversário',
    'quiz_link' => 'Clica <a href="'.$url.'/quiz">aqui</a> para jogar.',
    'specialquiz_available' => 'Hoje há quiz especial!',
    'specialquiz_available_deadline' => 'Não te esqueças de responder ao quiz especial de hoje!',
    'specialquiz_subject' => 'Título',
    'specialquiz_author' => 'Autor',
    'specialquiz_link' => 'Clica <a href="'.$url.'/special-quiz">aqui</a> para jogar.',
    'deadline' => 'Tens até à meia-noite para submeter as tuas respostas.',
    'from' => 'De',
    'rights' => 'Todos os direitos reservados.',
    'invite_message' => 'Foste convidado por um(a) jogador(a) actual (:name) para jogar a '.$app_name.'.<br>Caso queiras aceitar o convite, regista-te <a href="'.$url.'/register/" target="_blank">aqui</a>.',
    'quiz_submission_copy' => 'Abaixo está uma cópia da tua submissão.',
    'quiz_question' => 'Pergunta',
    'correct_answer' => 'Resposta correcta',
    'your_answer' => 'Resposta dada'
];
