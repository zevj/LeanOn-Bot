<template>
<main>
  <div class="login-container">

  <div class="left-container">
    <h1 class="login-title">Forgot your <span>Password?</span></h1>
    <p class="login-subtitle">Forgot the password, don't worry and we've got you back</p>

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

          <router-link to="/OTPVerification" type="submit" class="login-button">Enter</router-link>
          <router-link to="/login" type="submit" class="back-button">Back</router-link>
    </form>

    
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


<style scoped src="../assets/login/forgotPass.css"></style>