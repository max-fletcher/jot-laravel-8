<template>
    <div>
        <!-- need to use the @submit.prevent directive to prevent page reload otherwise it wouldn't be an SPA -->
        <form @submit.prevent="submitForm">
        <!-- sending props data into the input fields so that each will give us field with different labels & placeholders.
        @update:field is to send data directly to a method inside InputField isntead of a prop. There, it will be evaluated and
        stored into a variable called 'value'. -->
            <InputField name="name" label="Contact Name" :errors="errors"
             placeholder="Enter Contact Name" @update:field="form.name = $event" />
            <InputField name="email" label="E-mail" :errors="errors"
             placeholder="Enter E-Mail Address" @update:field="form.email = $event" />
            <InputField name="company" label="Company" :errors="errors"
             placeholder="Enter Company Name" @update:field="form.company = $event" />
            <InputField name="birthday" label="Birthday" :errors="errors"
             placeholder="MM/DD/YYYY" @update:field="form.birthday = $event" />
             <!-- submit and cancel buttons -->
            <div class="flex justify-end">
                <router-link to="/contacts" class="bg-white py-2 px-4 rounded text-red-700 border mr-5 hover:border-red-700"> Cancel </router-link>
                <button class="bg-blue-500 py-2 px-4 rounded text-white hover:bg-blue-400"> Submit </button>
            </div>
        </form>
    </div>
</template>

<script>
// import the InputField component from components folder
// Two dots means one directory back. The first dot is there by default and any subsequent dots represent how many directories back
    import InputField from '../components/InputField';

    export default {
        name: "ContactsCreate",
        mounted() {

        },
        
        components: {
            InputField
        },

        data: function() {
            return {
                form: {
                    'name' : '',
                    'email' : '',
                    'company' : '',
                    'birthday' : '',
                },

                errors: null,
            }
        },

        methods: {
            // submit form
            submitForm: function () {
                axios.post('/api/contacts', this.form)
                    .then(response => {
                        // pushes a new link into vue router. Basically redirects after submission
                        // (i.e after request receives a response)
                       this.$router.push(response.data.links.self);
                    })
                    .catch(errors => {
                    // "errors" variable in "data" is initially empty. Fill it with any errors that the backend provides
                    // (if any)when making the API call.
                       this.errors = errors.response.data.errors;
                    });
            }
        }
    }
</script>
