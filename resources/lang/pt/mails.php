<?php

$config = \Config::get('app');
$app_name = $config['name'];
$url = $config['url'];

return [
    'message_subject' => '['.$app_name.'] Sugestão/Reclamação',
    'reminder_subject' => '['.$app_name.'] Quiz disponível',
    'invitation_subject' => '['.$app_name.'] Convite',
    'new_user_subject' => '['.$app_name.'] Novo utilizador',
    'hello' => 'Olá',
    'quiz_available' => 'Já está disponível o quiz de hoje!',
    'quiz_available_deadline' => 'Só tens mais 2 horas para responder ao quiz de hoje!',
    'quiz_opponent' => 'O teu adversário',
    'quiz_link' => 'Clica <a href="'.$url.'/quiz">aqui</a> para jogar.',
    'specialquiz_available' => 'Hoje há quiz especial!',
    'specialquiz_available_deadline' => 'Só tens mais 2 horas para responder ao quiz especial de hoje!',
    'specialquiz_subject' => 'Tema',
    'specialquiz_author' => 'Autor',
    'specialquiz_link' => 'Clica <a href="'.$url.'/special-quiz">aqui</a> para jogar.',
    'deadline' => 'Tens até à meia-noite para submeter as tuas respostas.',
    'from' => 'De',
    'rights' => 'Todos os direitos reservados.',
    'invite_message' => 'Foste convidado por um(a) jogador(a) actual (:name) para jogar a '.$app_name.'.<br>Caso queiras aceitar o convite, regista-te <a href="'.$url.'/register/" target="_blank">aqui</a>.',
];
