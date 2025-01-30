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

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $profile_data = [
        'model_name' => $_POST['name'] ?? '',
        'username' => '@' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $_POST['name'])),
        'description' => $_POST['description'] ?? '',
        'location' => $_POST['location'] ?? 'São Paulo',
        'photo' => '',
        'banner' => '',
        'social_media' => [
            'instagram' => 'https://instagram.com/Mia.monroex',
            'twitter' => 'https://twitter.com/Collegestrippa'
        ],
        'stats' => [
            'photos' => '1.8K',
            'videos' => '272',
            'lives' => '39',
            'likes' => '1.67M'
        ],
        'content_counts' => [
            'posts' => '1,859',
            'photos' => '1,164',
            'videos' => '1,018'
        ],
        'subscription' => [
            'regular_price' => number_format(floatval($_POST['price'] ?? 0), 2, '.', ''),
            'promo_price' => number_format(floatval($_POST['super_offer'] ?? 0), 2, '.', ''),
            'promo_days' => '31',
            'promo_end_date' => 'jan 29',
            'promo_message' => '$3 for TODAY ONLY!!! 😇🌸 Hurry!! Cum sext with me! 💦'
        ],
        'bundles' => [
            '3months' => [
                'discount' => '15OFF',
                'price' => number_format(floatval($_POST['offer1'] ?? 0), 2, '.', '')
            ],
            '6months' => [
                'discount' => '30OFF',
                'price' => number_format(floatval($_POST['offer2'] ?? 0), 2, '.', '')
            ],
            '12months' => [
                'discount' => '45OFF',
                'price' => number_format(floatval($_POST['offer3'] ?? 0), 2, '.', '')
            ]
        ],
        'bio' => $_POST['description'] ?? '',
        'facebook_pixel' => [
            'id' => $_POST['fb_pixel_id'] ?? '',
            'token' => $_POST['fb_pixel_token'] ?? ''
        ],
        'updated_at' => date('Y-m-d H:i:s')
    ];

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

    // Adicione após o processamento do upload para debug
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
            'lives' => '39',
            'likes' => '1.67M'
        ],
        'content_counts' => [
            'posts' => '1,859',
            'photos' => '1,164',
            'videos' => '1,018'
        ],
        'subscription' => [
            'regular_price' => '30.00',
            'promo_price' => '37.00',
            'promo_days' => '31',
            'promo_end_date' => 'jan 29',
            'promo_message' => '$3 for TODAY ONLY!!! 😇🌸 Hurry!! Cum sext with me! 💦'
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

<div class="container">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Editar Perfil</h4>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
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

                        <div class="mb-4">
                            <h5>Valores das Ofertas</h5>
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

                        <div class="mb-4">
                            <h5>Facebook Pixel</h5>
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