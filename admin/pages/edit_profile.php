<?php
// Adicione no início do arquivo, após o PHP tag
error_reporting(E_ALL);
ini_set('display_errors', 1);

// No início do arquivo
if (!file_exists($_SERVER['DOCUMENT_ROOT'] . '/img/banners/')) {
    mkdir($_SERVER['DOCUMENT_ROOT'] . '/img/banners/', 0755, true);
}
if (!file_exists($_SERVER['DOCUMENT_ROOT'] . '/img/profile/')) {
    mkdir($_SERVER['DOCUMENT_ROOT'] . '/img/profile/', 0755, true);
}

// -- Carregar o perfil existente ANTES de processar o POST (para mesclar dados) --
$profile = [];
if (file_exists(__DIR__ . '/../../data/profile.json')) {
    $profile = json_decode(file_get_contents(__DIR__ . '/../../data/profile.json'), true) ?? [];
}

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /*
    // (SEU CÓDIGO ORIGINAL CRIANDO UM NOVO ARRAY DO ZERO)
    $profile_data = [
        ...
    ];
    */

    // INSTEAD OF ABOVE, WE MERGE O QUE JÁ EXISTE
    $profile_data = $profile;  // começa com os valores atuais gravados

    // Se o $_POST['name'] existir, atualiza (senão mantém o antigo)
    if (isset($_POST['name']) && $_POST['name'] !== '') {
        $profile_data['model_name'] = $_POST['name'];
        $profile_data['username']   = '@' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $_POST['name']));
    } else {
        if (!isset($profile_data['model_name'])) $profile_data['model_name'] = '';
        if (!isset($profile_data['username']))   $profile_data['username']   = '';
    }

    if (isset($_POST['description'])) {
        $profile_data['description'] = $_POST['description'];
        $profile_data['bio']         = $_POST['description'];
    } else {
        if (!isset($profile_data['description'])) $profile_data['description'] = '';
        if (!isset($profile_data['bio']))         $profile_data['bio']         = '';
    }

    if (isset($_POST['location'])) {
        $profile_data['location'] = $_POST['location'];
    } else {
        if (!isset($profile_data['location'])) $profile_data['location'] = 'São Paulo';
    }

    // EDITAR SOCIAL MEDIA (instagram, twitter)
    if (!isset($profile_data['social_media'])) {
        $profile_data['social_media'] = [
            'instagram' => 'https://instagram.com/Mia.monroex',
            'twitter'   => 'https://twitter.com/Collegestrippa'
        ];
    }
    if (isset($_POST['instagram'])) {
        $profile_data['social_media']['instagram'] = $_POST['instagram'];
    } else {
        if (!isset($profile_data['social_media']['instagram'])) {
            $profile_data['social_media']['instagram'] = 'https://instagram.com/Mia.monroex';
        }
    }
    if (isset($_POST['twitter'])) {
        $profile_data['social_media']['twitter'] = $_POST['twitter'];
    } else {
        if (!isset($profile_data['social_media']['twitter'])) {
            $profile_data['social_media']['twitter'] = 'https://twitter.com/Collegestrippa';
        }
    }

    // EDITAR STATS (photos, videos, lives, likes)
    if (!isset($profile_data['stats'])) {
        $profile_data['stats'] = [
            'photos' => '1.8K',
            'videos' => '272',
            'lives'  => '39',
            'likes'  => '1.67M'
        ];
    }
    if (isset($_POST['stats_photos'])) {
        $profile_data['stats']['photos'] = $_POST['stats_photos'];
    } else {
        if (!isset($profile_data['stats']['photos'])) {
            $profile_data['stats']['photos'] = '1.8K';
        }
    }
    if (isset($_POST['stats_videos'])) {
        $profile_data['stats']['videos'] = $_POST['stats_videos'];
    } else {
        if (!isset($profile_data['stats']['videos'])) {
            $profile_data['stats']['videos'] = '272';
        }
    }
    if (isset($_POST['stats_lives'])) {
        $profile_data['stats']['lives'] = $_POST['stats_lives'];
    } else {
        if (!isset($profile_data['stats']['lives'])) {
            $profile_data['stats']['lives'] = '39';
        }
    }
    if (isset($_POST['stats_likes'])) {
        $profile_data['stats']['likes'] = $_POST['stats_likes'];
    } else {
        if (!isset($profile_data['stats']['likes'])) {
            $profile_data['stats']['likes'] = '1.67M';
        }
    }

    // EDITAR CONTENT_COUNTS (posts, photos, videos)
    if (!isset($profile_data['content_counts'])) {
        $profile_data['content_counts'] = [
            'posts'  => '1,859',
            'photos' => '1,164',
            'videos' => '1,018'
        ];
    }
    if (isset($_POST['count_posts'])) {
        $profile_data['content_counts']['posts'] = $_POST['count_posts'];
    } else {
        if (!isset($profile_data['content_counts']['posts'])) {
            $profile_data['content_counts']['posts'] = '1,859';
        }
    }
    if (isset($_POST['count_photos'])) {
        $profile_data['content_counts']['photos'] = $_POST['count_photos'];
    } else {
        if (!isset($profile_data['content_counts']['photos'])) {
            $profile_data['content_counts']['photos'] = '1,164';
        }
    }
    if (isset($_POST['count_videos'])) {
        $profile_data['content_counts']['videos'] = $_POST['count_videos'];
    } else {
        if (!isset($profile_data['content_counts']['videos'])) {
            $profile_data['content_counts']['videos'] = '1,018';
        }
    }

    // Se a secao subscription não existir ainda, criar
    if (!isset($profile_data['subscription'])) {
        $profile_data['subscription'] = [];
    }

    // Preço Regular
    if (isset($_POST['price'])) {
        $profile_data['subscription']['regular_price'] = number_format(floatval($_POST['price']), 2, '.', '');
    } else {
        if (!isset($profile_data['subscription']['regular_price'])) {
            $profile_data['subscription']['regular_price'] = '30.00';
        }
    }
    // Super Oferta
    if (isset($_POST['super_offer'])) {
        $profile_data['subscription']['promo_price'] = number_format(floatval($_POST['super_offer']), 2, '.', '');
    } else {
        if (!isset($profile_data['subscription']['promo_price'])) {
            $profile_data['subscription']['promo_price'] = '37.00';
        }
    }
    // Mantém fixos
    if (!isset($profile_data['subscription']['promo_days'])) {
        $profile_data['subscription']['promo_days'] = '31';
    }
    if (!isset($profile_data['subscription']['promo_end_date'])) {
        $profile_data['subscription']['promo_end_date'] = 'jan 29';
    }

    // "promo_message" => texto do "$3 for TODAY ONLY!!! 😇🌸 Hurry!!..."
    if (isset($_POST['promo_message'])) {
        $profile_data['subscription']['promo_message'] = $_POST['promo_message'];
    } else {
        if (!isset($profile_data['subscription']['promo_message'])) {
            $profile_data['subscription']['promo_message'] = '$3 for TODAY ONLY!!! 😇🌸 Hurry!! Cum sext with me! 💦';
        }
    }

    // "promo_headline"
    if (isset($_POST['promo_headline'])) {
        $profile_data['subscription']['promo_headline'] = $_POST['promo_headline'];
    } else {
        if (!isset($profile_data['subscription']['promo_headline'])) {
            $profile_data['subscription']['promo_headline'] = 'Limited offer: 90% off first';
        }
    }

    // Bundles
    if (!isset($profile_data['bundles'])) {
        $profile_data['bundles'] = [];
    }
    // 3months
    if (!isset($profile_data['bundles']['3months'])) {
        $profile_data['bundles']['3months'] = [];
    }
    if (isset($_POST['offer1'])) {
        $profile_data['bundles']['3months']['price'] = number_format(floatval($_POST['offer1']), 2, '.', '');
    } else {
        if (!isset($profile_data['bundles']['3months']['price'])) {
            $profile_data['bundles']['3months']['price'] = '44.00';
        }
    }
    if (!isset($profile_data['bundles']['3months']['discount'])) {
        $profile_data['bundles']['3months']['discount'] = '15OFF';
    }
    // 6months
    if (!isset($profile_data['bundles']['6months'])) {
        $profile_data['bundles']['6months'] = [];
    }
    if (isset($_POST['offer2'])) {
        $profile_data['bundles']['6months']['price'] = number_format(floatval($_POST['offer2']), 2, '.', '');
    } else {
        if (!isset($profile_data['bundles']['6months']['price'])) {
            $profile_data['bundles']['6months']['price'] = '55.00';
        }
    }
    if (!isset($profile_data['bundles']['6months']['discount'])) {
        $profile_data['bundles']['6months']['discount'] = '30OFF';
    }
    // 12months
    if (!isset($profile_data['bundles']['12months'])) {
        $profile_data['bundles']['12months'] = [];
    }
    if (isset($_POST['offer3'])) {
        $profile_data['bundles']['12months']['price'] = number_format(floatval($_POST['offer3']), 2, '.', '');
    } else {
        if (!isset($profile_data['bundles']['12months']['price'])) {
            $profile_data['bundles']['12months']['price'] = '77.00';
        }
    }
    if (!isset($profile_data['bundles']['12months']['discount'])) {
        $profile_data['bundles']['12months']['discount'] = '45OFF';
    }

    // facebook_pixel
    if (!isset($profile_data['facebook_pixel'])) {
        $profile_data['facebook_pixel'] = [];
    }
    if (isset($_POST['fb_pixel_id'])) {
        $profile_data['facebook_pixel']['id'] = $_POST['fb_pixel_id'];
    } else {
        if (!isset($profile_data['facebook_pixel']['id'])) {
            $profile_data['facebook_pixel']['id'] = '';
        }
    }
    if (isset($_POST['fb_pixel_token'])) {
        $profile_data['facebook_pixel']['token'] = $_POST['fb_pixel_token'];
    } else {
        if (!isset($profile_data['facebook_pixel']['token'])) {
            $profile_data['facebook_pixel']['token'] = '';
        }
    }

    // updated_at
    $profile_data['updated_at'] = date('Y-m-d H:i:s');

    // Processar upload de imagem de perfil
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/img/profile/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_extension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = 'profile_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                $profile_data['photo'] = '/img/profile/' . $new_filename;
            }
        }
    }

    // Processar upload do banner
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/img/banners/';
        $file_extension = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = 'banner_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $upload_path)) {
                $profile_data['banner'] = '/img/banners/' . $new_filename;
            }
        }
    }

    // Manter as imagens atuais se não houver novo upload
    if (empty($profile_data['photo']) && isset($profile['photo'])) {
        $profile_data['photo'] = $profile['photo'];
    }
    if (empty($profile_data['banner']) && isset($profile['banner'])) {
        $profile_data['banner'] = $profile['banner'];
    }

    // Log de debug do upload, se ocorrer
    if (isset($_FILES['profile_image']) || isset($_FILES['banner_image'])) {
        error_log('Upload attempt: ' . print_r($_FILES, true));
    }

    // Salvar os dados
    if (file_put_contents(__DIR__ . '/../../data/profile.json', json_encode($profile_data, JSON_PRETTY_PRINT))) {
        $_SESSION['message'] = 'Perfil atualizado com sucesso!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Erro ao atualizar o perfil.';
        $_SESSION['message_type'] = 'danger';
    }

    header('Location: index.php?page=edit_profile');
    exit;
}

// Carregar perfil atual
if (!file_exists(__DIR__ . '/../../data/profile.json')) {
    $default_profile = [
        'model_name' => '',
        'username' => '',
        'photo' => '',
        'banner' => '',
        'description' => '',
        'location' => '',
        'social_media' => [
            'instagram' => 'https://instagram.com/Mia.monroex',
            'twitter' => 'https://twitter.com/Collegestrippa'
        ],
        'stats' => [
            'photos' => '1.8K',
            'videos' => '272',
            'lives'  => '39',
            'likes'  => '1.67M'
        ],
        'content_counts' => [
            'posts'  => '1,859',
            'photos' => '1,164',
            'videos' => '1,018'
        ],
        'subscription' => [
            'regular_price' => '30.00',
            'promo_price' => '37.00',
            'promo_days' => '31',
            'promo_end_date' => 'jan 29',
            'promo_message' => '$3 for TODAY ONLY!!! 😇🌸 Hurry!! Cum sext with me! 💦',
            'promo_headline' => 'Limited offer: 90% off first'
        ],
        'bundles' => [
            '3months' => [
                'discount' => '15OFF',
                'price' => '44.00'
            ],
            '6months' => [
                'discount' => '30OFF',
                'price' => '55.00'
            ],
            '12months' => [
                'discount' => '45OFF',
                'price' => '77.00'
            ]
        ],
        'bio' => '',
        'facebook_pixel' => [
            'id' => '',
            'token' => ''
        ]
    ];
    file_put_contents(__DIR__ . '/../../data/profile.json', json_encode($default_profile, JSON_PRETTY_PRINT));
}
?>

<!-- ======= ESTILO CUSTOM: TEMA ESCURO, SOMBRIO, HACKER, MALIGNO, E RESPONSIVO ======= -->
<style>
@import url('https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap');

body {
    background: #0A0A0A;
    color: #BFBFBF;
    margin: 0;
    padding: 0;
}
* {
    font-family: 'Share Tech Mono', monospace;
    box-sizing: border-box;
}
.container {
    width: 100%;
    max-width: 1400px; 
    margin: 2rem auto;
    padding: 0 1rem;
}
.card {
    background: #121212;
    border: 1px solid #222;
    border-radius: 10px;
    margin-bottom: 2rem;
}
.card-header {
    background: #1b1b1b;
    border-bottom: 1px solid #222;
    border-radius: 10px 10px 0 0;
    color: #FF4444; /* cor sanguinária */
}
.card-body {
    background: #1b1b1b;
    border-radius: 0 0 10px 10px;
}
.card-header h4 {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 600;
}
.form-label {
    color: #FF5555;
    font-weight: 500;
    margin-bottom: 0.4rem;
}
.form-control {
    background: #151515;
    color: #cfcfcf;
    border: 1px solid #333;
}
.form-control:focus {
    background: #151515;
    color: #cfcfcf;
    outline: none;
    box-shadow: 0 0 3px #FF4444;
}
.small.text-muted {
    color: #666 !important;
}
.section-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 1.2rem;
    color: #FF2E2E;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.section-title i {
    color: #FF6C6C;
}
.img-thumbnail {
    background: #151515;
    border: 1px solid #333;
}
.btn-primary {
    background: linear-gradient(45deg, #BF0000, #FF4444);
    border: none;
    color: #fff;
    font-weight: 600;
    transition: background 0.3s ease;
}
.btn-primary:hover {
    background: linear-gradient(45deg, #9e0000, #f13131);
    color: #fff;
}
.d-grid.gap-2 button[type="submit"] i {
    margin-right: 0.4rem;
}

/* Responsividade maior, layout fluido */
@media (min-width: 768px) {
    .row {
        display: flex;
        flex-wrap: wrap;
    }
    .col-md-8 {
        flex: 0 0 auto;
        width: 66.6667%;
        margin: 0 auto;
    }
}
@media (max-width: 767px) {
    .section-title {
        font-size: 1rem;
    }
    .card-header h4 {
        font-size: 1.1rem;
    }
    .d-grid.gap-2 {
        margin-top: 1rem;
    }
}
</style>
<!-- ======= FIM ESTILO CUSTOM ======= -->

<div class="container">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">
                        <i class="bi bi-emoji-angry-fill"></i> 
                        Editar Perfil
                    </h4>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">

                        <!-- ETAPA 1: Dados Básicos -->
                        <div class="mb-4">
                            <div class="section-title">
                                <i class="bi bi-file-person-fill"></i>
                                DADOS BÁSICOS
                            </div>
                            <div class="mb-3">
                                <label for="name" class="form-label">Nome</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="name" 
                                       name="name" 
                                       value="<?php echo htmlspecialchars($profile['model_name'] ?? ''); ?>" 
                                       required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Descrição/Bio</label>
                                <textarea class="form-control" 
                                          id="description" 
                                          name="description" 
                                          rows="4" 
                                          required><?php echo htmlspecialchars($profile['bio'] ?? ''); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="location" class="form-label">Localização</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="location" 
                                       name="location" 
                                       value="<?php echo htmlspecialchars($profile['location'] ?? 'São Paulo'); ?>" 
                                       required>
                                <small class="text-muted">Ex: São Paulo, Rio de Janeiro, etc.</small>
                            </div>
                        </div>
                        <!-- FIM ETAPA 1 -->

                        <!-- ETAPA 2: Redes Sociais -->
                        <div class="mb-4">
                            <div class="section-title">
                                <i class="bi bi-hdd-network-fill"></i>
                                REDES SOCIAIS
                            </div>
                            <div class="mb-3">
                                <label for="instagram" class="form-label">Instagram URL</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="instagram" 
                                       name="instagram" 
                                       value="<?php echo htmlspecialchars($profile['social_media']['instagram'] ?? 'https://instagram.com/Mia.monroex'); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="twitter" class="form-label">Twitter URL</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="twitter" 
                                       name="twitter" 
                                       value="<?php echo htmlspecialchars($profile['social_media']['twitter'] ?? 'https://twitter.com/Collegestrippa'); ?>">
                            </div>
                        </div>
                        <!-- FIM ETAPA 2 -->

                        <!-- ETAPA 3: Estatísticas -->
                        <div class="mb-4">
                            <div class="section-title">
                                <i class="bi bi-bar-chart-fill"></i>
                                ESTATÍSTICAS (STATS)
                            </div>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label for="stats_photos" class="form-label">Photos</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="stats_photos" 
                                           name="stats_photos" 
                                           value="<?php echo htmlspecialchars($profile['stats']['photos'] ?? '1.8K'); ?>">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="stats_videos" class="form-label">Videos</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="stats_videos" 
                                           name="stats_videos" 
                                           value="<?php echo htmlspecialchars($profile['stats']['videos'] ?? '272'); ?>">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="stats_lives" class="form-label">Lives</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="stats_lives" 
                                           name="stats_lives" 
                                           value="<?php echo htmlspecialchars($profile['stats']['lives'] ?? '39'); ?>">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="stats_likes" class="form-label">Likes</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="stats_likes" 
                                           name="stats_likes" 
                                           value="<?php echo htmlspecialchars($profile['stats']['likes'] ?? '1.67M'); ?>">
                                </div>
                            </div>
                        </div>
                        <!-- FIM ETAPA 3 -->

                        <!-- ETAPA 4: Contagem de Conteúdos -->
                        <div class="mb-4">
                            <div class="section-title">
                                <i class="bi bi-collection"></i>
                                CONTAGEM DE CONTEÚDOS
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="count_posts" class="form-label">Posts</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="count_posts" 
                                           name="count_posts" 
                                           value="<?php echo htmlspecialchars($profile['content_counts']['posts'] ?? '1,859'); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="count_photos" class="form-label">Photos</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="count_photos" 
                                           name="count_photos" 
                                           value="<?php echo htmlspecialchars($profile['content_counts']['photos'] ?? '1,164'); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="count_videos" class="form-label">Videos</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="count_videos" 
                                           name="count_videos" 
                                           value="<?php echo htmlspecialchars($profile['content_counts']['videos'] ?? '1,018'); ?>">
                                </div>
                            </div>
                        </div>
                        <!-- FIM ETAPA 4 -->

                        <!-- ETAPA 5: Valores das Ofertas -->
                        <div class="mb-4">
                            <div class="section-title">
                                <i class="bi bi-currency-bitcoin"></i>
                                VALORES DAS OFERTAS
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="super_offer" class="form-label">Super Oferta (R$)</label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="super_offer" 
                                           name="super_offer" 
                                           step="0.01" 
                                           value="<?php echo htmlspecialchars($profile['subscription']['promo_price'] ?? '37.00'); ?>" 
                                           required>
                                    <small class="text-muted">Valor da super oferta em destaque</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="price" class="form-label">Preço Regular (R$)</label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="price" 
                                           name="price" 
                                           step="0.01" 
                                           value="<?php echo htmlspecialchars($profile['subscription']['regular_price'] ?? '30.00'); ?>" 
                                           required>
                                    <small class="text-muted">Preço regular sem desconto</small>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="offer1" class="form-label">Oferta 1 (R$)</label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="offer1" 
                                           name="offer1" 
                                           step="0.01" 
                                           value="<?php echo htmlspecialchars($profile['bundles']['3months']['price'] ?? '44.00'); ?>" 
                                           required>
                                    <small class="text-muted">Valor da primeira oferta</small>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="offer2" class="form-label">Oferta 2 (R$)</label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="offer2" 
                                           name="offer2" 
                                           step="0.01" 
                                           value="<?php echo htmlspecialchars($profile['bundles']['6months']['price'] ?? '55.00'); ?>" 
                                           required>
                                    <small class="text-muted">Valor da segunda oferta</small>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="offer3" class="form-label">Oferta 3 (R$)</label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="offer3" 
                                           name="offer3" 
                                           step="0.01" 
                                           value="<?php echo htmlspecialchars($profile['bundles']['12months']['price'] ?? '77.00'); ?>" 
                                           required>
                                    <small class="text-muted">Valor da terceira oferta</small>
                                </div>
                            </div>
                        </div>
                        <!-- FIM ETAPA 5 -->

                        <!-- ETAPA 6: Upload de Imagens -->
                        <div class="mb-4">
                            <div class="section-title">
                                <i class="bi bi-image-fill"></i>
                                IMAGENS DO PERFIL
                            </div>
                            <div class="mb-3">
                                <label for="profile_image" class="form-label">Foto de Perfil</label>
                                <?php if (!empty($profile['photo'])): ?>
                                    <div class="mb-2">
                                        <img src="<?php echo htmlspecialchars($profile['photo']); ?>" 
                                             alt="Imagem atual" 
                                             class="img-thumbnail" 
                                             style="max-width: 200px;">
                                    </div>
                                <?php endif; ?>
                                <input type="file" 
                                       class="form-control" 
                                       id="profile_image" 
                                       name="profile_image" 
                                       accept="image/*">
                                <small class="form-text text-muted">
                                    Deixe em branco para manter a imagem atual. Formatos aceitos: JPG, JPEG, PNG, GIF
                                </small>
                            </div>

                            <div class="mb-3">
                                <label for="banner_image" class="form-label">Banner do Perfil</label>
                                <?php if (!empty($profile['banner'])): ?>
                                    <div class="mb-2">
                                        <img src="<?php echo htmlspecialchars($profile['banner']); ?>" 
                                             alt="Banner atual" 
                                             class="img-thumbnail" 
                                             style="max-width: 100%; height: auto;">
                                    </div>
                                <?php endif; ?>
                                <input type="file" 
                                       class="form-control" 
                                       id="banner_image" 
                                       name="banner_image" 
                                       accept="image/*">
                                <small class="form-text text-muted">
                                    Tamanho recomendado: 1200x300 pixels. Formatos aceitos: JPG, JPEG, PNG, GIF
                                </small>
                            </div>
                        </div>
                        <!-- FIM ETAPA 6 -->

                        <!-- ETAPA 7: Facebook Pixel -->
                        <div class="mb-4">
                            <div class="section-title">
                                <i class="bi bi-bug-fill"></i>
                                FACEBOOK PIXEL
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="fb_pixel_id" class="form-label">Pixel ID</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="fb_pixel_id" 
                                           name="fb_pixel_id" 
                                           value="<?php echo htmlspecialchars($profile['facebook_pixel']['id'] ?? ''); ?>" 
                                           placeholder="Ex: 123456789012345">
                                    <small class="text-muted">ID do seu Pixel do Facebook (opcional)</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="fb_pixel_token" class="form-label">Token de Acesso</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="fb_pixel_token" 
                                           name="fb_pixel_token" 
                                           value="<?php echo htmlspecialchars($profile['facebook_pixel']['token'] ?? ''); ?>" 
                                           placeholder="Ex: EAAxxxx...">
                                    <small class="text-muted">Token de acesso do Pixel (opcional)</small>
                                </div>
                            </div>
                        </div>
                        <!-- FIM ETAPA 7 -->

                        <!-- ETAPA 8: Texto Promocional -->
                        <div class="mb-4">
                            <div class="section-title">
                                <i class="bi bi-megaphone"></i>
                                TEXTO PROMOCIONAL
                            </div>
                            <div class="mb-3">
                                <label for="promo_message" class="form-label">Mensagem Promo (ex: $3 for TODAY ONLY)</label>
                                <textarea class="form-control"
                                          id="promo_message"
                                          name="promo_message"
                                          rows="2"><?php echo htmlspecialchars($profile['subscription']['promo_message'] ?? '$3 for TODAY ONLY!!! 😇🌸 Hurry!! Cum sext with me! 💦'); ?></textarea>
                                <small class="text-muted">Ex: "$3 for TODAY ONLY!!! 😇🌸 Hurry!! Cum sext with me! 💦"</small>
                            </div>

                            <div class="mb-3">
                                <label for="promo_headline" class="form-label">Headline Promo (ex: Limited offer...)</label>
                                <input type="text"
                                       class="form-control"
                                       id="promo_headline"
                                       name="promo_headline"
                                       value="<?php echo htmlspecialchars($profile['subscription']['promo_headline'] ?? 'Limited offer: 90% off first'); ?>">
                                <small class="text-muted">Ex: "Limited offer: 90% off first"</small>
                            </div>
                        </div>
                        <!-- FIM ETAPA 8 -->

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i>
                                Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
