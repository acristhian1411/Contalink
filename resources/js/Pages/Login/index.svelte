<script>
    import { Alert } from '@/components/Alerts';
    import { Inertia } from '@inertiajs/inertia';
    let email = '';
    let password = '';
    let remember = false;
    let alertMessage = '';
    let alertType = '';
	  let openAlert = false;

    function closeAlert() {
		openAlert = false;
	}

	function OpenAlertMessage(event) {
		openAlert = true;
		alertType = event.detail.type;
		alertMessage = event.detail.message;
	}
    const login = async () => {
      try {
        const response = await axios.post('/login', {
          email,
          password,
          remember
        });
  
        if (response.data.success) {
          OpenAlertMessage({detail: {type: 'success', message: 'Inicio de sesión exitoso'}});
          setTimeout(() => {
            Inertia.visit('/');
          }, 500);
        }
      } catch (error) {
        if (error.response) {
          // Handle different error status codes
          if (error.response.status === 422) {
            // Validation errors (invalid credentials)
            const errors = error.response.data.errors;
            const errorMessage = errors.email?.[0] || errors.password?.[0] || 'Credenciales inválidas';
            OpenAlertMessage({detail: {type: 'error', message: errorMessage}});
          } else if (error.response.status === 419) {
            // CSRF token error
            OpenAlertMessage({detail: {type: 'error', message: 'Su sesión ha expirado. Por favor, recargue la página e intente nuevamente.'}});
          } else if (error.response.status === 401) {
            // Authentication error
            OpenAlertMessage({detail: {type: 'error', message: 'No autorizado. Verifique sus credenciales.'}});
          } else {
            // Generic error
            OpenAlertMessage({detail: {type: 'error', message: 'Error al iniciar sesión. Por favor, intente nuevamente.'}});
          }
        } else {
          // Network or other errors
          OpenAlertMessage({detail: {type: 'error', message: 'Error de conexión. Por favor, verifique su conexión a internet.'}});
        }
      }
    };
  </script>
  {#if openAlert}
    <Alert {alertMessage} {alertType} on:close={closeAlert} />
  {/if}
  <!-- <form on:submit|preventDefault={login}>
    <input type="email" bind:value={email} placeholder="Email" required />
    <input type="password" bind:value={password} placeholder="Password" required />
    <button type="submit">Login</button>
  </form> -->
  <section >
    <div class="flex flex-col items-center justify-center px-6 py-8 mx-auto md:h-screen lg:py-0">
        <!-- svelte-ignore a11y-invalid-attribute -->
        <!-- <a href="#" class="flex items-center mb-6 text-2xl font-semibold text-gray-900 dark:text-white">
            <img class="w-8 h-8 mr-2" src="https://flowbite.s3.amazonaws.com/blocks/marketing-ui/logo.svg" alt="logo">
            Flowbite    
        </a> -->
        <div class="w-full bg-white rounded-lg shadow dark:border md:mt-0 sm:max-w-md xl:p-0 dark:bg-gray-800 dark:border-gray-700">
            <div class="p-6 space-y-4 md:space-y-6 sm:p-8">
                <h1 class="text-xl font-bold leading-tight tracking-tight text-gray-900 md:text-2xl dark:text-white">
                    Inicia sesión
                </h1>
                <form on:submit|preventDefault={login} class="space-y-4 md:space-y-6" action="#">
                    <div>
                        <label for="email" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Correo</label>
                        <input type="email" bind:value={email} name="email" id="email" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="nombre@ejemplo.com" required="">
                    </div>
                    <div>
                        <label for="password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Contraseña</label>
                        <input type="password" name="password" bind:value={password} id="password" placeholder="••••••••" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required="">
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                              <input id="remember" bind:checked={remember} aria-describedby="remember" type="checkbox" class="w-4 h-4 border border-gray-300 rounded bg-gray-50 focus:ring-3 focus:ring-primary-300 dark:bg-gray-700 dark:border-gray-600 dark:focus:ring-primary-600 dark:ring-offset-gray-800">
                            </div>
                            <div class="ml-3 text-sm">
                              <label for="remember" class="text-gray-500 dark:text-gray-300">Recordarme</label>
                            </div>
                        </div>
                        <!-- svelte-ignore a11y-invalid-attribute -->
                        <a href="#" class="text-sm font-medium text-primary-600 hover:underline dark:text-primary-500">Olvidó su contraseña?</a>
                    </div>
                    <button  type="submit" class="w-full text-white bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800">Iniciar sesión</button>
                    <p class="text-sm font-light text-gray-500 dark:text-gray-400">
                        <!-- svelte-ignore a11y-invalid-attribute -->
                        Todavía no tiene una cuenta? <a href="#" class="font-medium text-primary-600 hover:underline dark:text-primary-500">Registrarse</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
  </section>