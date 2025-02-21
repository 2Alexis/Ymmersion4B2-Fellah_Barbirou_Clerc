<?php
class NotificationManager {
    private $from = 'alexisclerc22@gmail.com';  // Remplacez par votre email

    public function sendOrderStatusUpdate($order) {
        $to = $order['client_email'];
        $subject = "Mise à jour de votre commande #" . $order['id'];
        
        $message = "Bonjour " . $order['client_name'] . ",\n\n";
        $message .= "Votre commande #" . $order['id'] . " est maintenant " . $order['status'] . ".\n";
        $message .= "Fichier : " . $order['stl_file'] . "\n";
        $message .= "Type de filament : " . $order['filament_type'] . "\n\n";
        $message .= "Merci d'utiliser notre service d'impression 3D!\n";
        
        $headers = "From: " . $this->from . "\r\n";
        
        mail($to, $subject, $message, $headers);
    }
} 