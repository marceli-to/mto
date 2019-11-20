<template>
    <div class="container-auth">
        <main class="content-auth" role="main">
            <header class="auth-header">
                <h1>Login</h1>
            </header>
            <form method="POST" class="login" v-on:submit.prevent="submitLogin">
                <div class="form-row">
                    <label>E-Mail</label>
                    <input type="email" required autofocus autocomplete="email" v-model="email">
                </div>
                <div class="form-row">
                    <label>Password</label>
                    <input id="password" type="password" required v-model="password">
                </div>
                <div class="form-row is-last">
                    <div class="form-buttons">
                        <input type="submit" class="btn" value="Login">
                    </div>
                </div>
            </form>
        </main>
    </div>
</template>

<script>
    import store from '../../store'
    export default {
        data() {
            return {
                email: '',
                password: '',
                loginError: false,
            }
        },
        methods: {
            submitLogin() {
                this.loginError = false;
                axios.post('/api/auth/login', {
                    email: this.email,
                    password: this.password
                }).then(response => {
                    // login user, store the token and redirect to dashboard
                    store.commit('loginUser')
                    localStorage.setItem('token', response.data.access_token)
                    // this.$router.push({ name: 'dashboard' })
                    window.location.href = '/admin/projects';
                }).catch(error => {
                    this.loginError = true
                });
            }
        }
    }
</script>