<template>
<main>
  <div class="login-container">

  <div class="left-container">
    <h1 class="login-title">Welcome <span>LeanOn Bot</span></h1>
    <p class="login-subtitle">Your safe space for mental wellness</p>

    <!-- Login Form -->
    <form class="login-form" @submit.prevent="handleLogin">
      <div class="form-group">
        <label for="username">Email</label>
        <input
          type="text"
          id="username"
          placeholder="Enter your email"
          v-model="username"
        />
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input
          type="password"
          id="password"
          placeholder="Enter your password"
          v-model="password"
        />
      </div>

      <router-link to="/forgotPass" class="forgot-password">Forgot Password?</router-link>
      <router-link to="/" type="submit" class="login-button">Sign In</router-link>

      <!-- SUBJECT TO CHANGE -->
      <div class="divider">
      <span></span>
      <p>or</p>
      <span></span>
    </div>

    <!-- Google Sign-In Button -->
    <button class="google-signin">
      Sign in with Google Account
    </button>

    <!-- Footer link -->
    <p class="new-student">
      New Student? <router-link to="/signup">Learn more about LeanOn Bot</router-link>
    </p>
    </form>

    <!-- Divider -->
    
  </div>

    <div class="right-container">

      <div class="overlay"></div>

      <div class="headings">
        <h1 class="title">LeanOn <span>Bot</span></h1>
        <p class="subtitle">Here When You Need Support.</p>

        <div class="yellow-line"></div>

        <p class="subheading">
          An AI-Assisted Mental Health Wellness Support System for Students
        </p>
      </div>

      <div class="footer">
        <div class="footer-container">

          <div class="features">
            <div class="green-circle"></div>
            <p class="feature-text">24/7 Available</p>
          </div>

          <div class="features">
            <div class="green-circle"></div>
            <p class="feature-text">Student Privacy</p>
          </div>

          <div class="features">
            <div class="green-circle"></div>
            <p class="feature-text">Fully Confidential</p>
          </div>

        </div>
      </div>

    </div>

  </div>
</main>
</template>

<script setup>

</script>


<style scoped src="../assets/login/Login.css"></style>