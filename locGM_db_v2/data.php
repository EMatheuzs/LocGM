<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
function seed_data()
{
    if (!isset($_SESSION['seeded'])) {
        $_SESSION['seeded'] = true;
        $_SESSION['companies'] = [
            ['email' => 'cheff@locgm.com', 'company_name' => 'Restaurante Cheff LocGM', 'address' => 'Av. Quintino Bocaiúva, Centro', 'phone' => '(69) 99999-0001'],
            ['email' => 'pousada@locgm.com', 'company_name' => 'Pousada Rio Mamoré', 'address' => 'R. Cunha Matos', 'phone' => '(69) 99999-0002'],
            ['email' => 'mercado@locgm.com', 'company_name' => 'Mercado Bom Preço', 'address' => 'Av. Costa Marques', 'phone' => '(69) 99999-0003']
        ];
        $_SESSION['demo_visitors'] = [['email' => 'visitante@locgm.com', 'name' => 'Visitante Demo', 'phone' => '(69) 90000-0000']];
        $_SESSION['places'] = [

            ['id' => 1, 'name' => 'Cheff LocGM', 'type' => 'restaurante', 'lat' => -10.7835, 'lng' => -65.3380, 'rating' => 4.6, 'address' => 'Centro', 'company_email' => 'cheff@locgm.com'],
            ['id' => 2, 'name' => 'Pousada Rio Mamoré', 'type' => 'pousada', 'lat' => -10.7819, 'lng' => -65.3395, 'rating' => 4.4, 'address' => 'Centro', 'company_email' => 'pousada@locgm.com'],
            ['id' => 3, 'name' => 'Mercado Bom Preço', 'type' => 'mercado', 'lat' => -10.7842, 'lng' => -65.3355, 'rating' => 4.1, 'address' => 'Centro', 'company_email' => 'mercado@locgm.com'],
            ['id' => 4, 'name' => 'Farmácia Mamoré', 'type' => 'farmacia', 'lat' => -10.7828, 'lng' => -65.3368, 'rating' => 4.3, 'address' => 'Centro', 'company_email' => null],
            ['id' => 5, 'name' => 'Praça do Madeira', 'type' => 'turismo', 'lat' => -10.7852, 'lng' => -65.3401, 'rating' => 4.7, 'address' => 'Praça', 'company_email' => null]
        ];
        $_SESSION['posts'] = [
            ['company_email' => 'cheff@locgm.com', 'company_name' => 'Restaurante Cheff LocGM', 'content' => 'Promoção: almoço executivo 12h - 14h por R$ 24,90!', 'created_at' => date('Y-m-d H:i')],
            ['company_email' => 'pousada@locgm.com', 'company_name' => 'Pousada Rio Mamoré', 'content' => 'Fim de semana com 10% OFF em reservas feitas pelo LocGM.', 'created_at' => date('Y-m-d H:i')]
        ];
        $_SESSION['messages'] = [];
        $_SESSION['next_place_id'] = 6;
    }
}
function current_user()
{
    return $_SESSION['user'] ?? null;
}
function set_user($u)
{
    $_SESSION['user'] = $u;
}
function require_login()
{
    if (!current_user()) {
        header('Location: /index.php');
        exit;
    }
}
function companies()
{
    seed_data();
    return $_SESSION['companies'];
}
function demo_visitors()
{
    seed_data();
    return $_SESSION['demo_visitors'];
}
function places()
{
    seed_data();
    return $_SESSION['places'];
}
function posts()
{
    seed_data();
    return $_SESSION['posts'];
}
function messages()
{
    seed_data();
    return $_SESSION['messages'];
}
function add_post($company_email, $company_name, $content)
{
    $_SESSION['posts'][] = ['company_email' => $company_email, 'company_name' => $company_name, 'content' => $content, 'created_at' => date('Y-m-d H:i')];
}
function add_message($from, $to, $content)
{
    $_SESSION['messages'][] = ['from' => $from, 'to' => $to, 'content' => $content, 'created_at' => date('H:i')];
}
function my_places($company_email)
{
    return array_values(array_filter(places(), fn($p) => $p['company_email'] === $company_email));
}
function save_place($data, $company_email)
{
    if (!empty($data['id'])) {
        foreach ($_SESSION['places'] as &$p) {
            if ($p['id'] == $data['id'] && $p['company_email'] === $company_email) {
                $p = array_merge($p, $data);
                return $p;
            }
        }
        return null;
    } else {
        $data['id'] = $_SESSION['next_place_id']++;
        $data['company_email'] = $company_email;
        $_SESSION['places'][] = $data;
        return end($_SESSION['places']);
    }
}
function get_place($id)
{
    foreach (places() as $p) {
        if ($p['id'] == $id)
            return $p;
    }
    return null;
}
?>