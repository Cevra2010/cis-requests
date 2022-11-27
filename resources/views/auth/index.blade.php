@extends("layout.auth")

@section("content")
    <div>
        <div class="bg-slate-700 text-slate-100 py-5 px-10 rounded-t shadow">
            CIS-Requests
        </div>
        <div class="bg-slate-100 p-10 rounded-b w-full">
            @include("layout.error_success")
            <form action="{{ route("auth.login.submit") }}" method="POST">
                @csrf
                <div class="cis-form-group">
                    <label for="email">E-Mail Adresse</label>
                    <input type="text" name="email" @error("email") class="is-invalid" @enderror value="{{ old("email") }}" id="email">
                </div>
                <div class="cis-form-group mt-6">
                    <label for="password">Passwort</label>
                    <input type="password" @error("password") class="is-invalid" @enderror value="{{ old("password") }}" name="password" id="password">
                </div>
                <button class="cis-submit w-full" type="submit">Anmelden</button>
            </form>

            <div class="mt-8 text-sm">
                <div>
                    <a href="#forgetPassword"><i class="fa fa-key"></i>  Passwort vergessen?</a>
                </div>
            </div>
        </div>
        <div class="text-slate-800 text-sm text-center mt-4">
            CisReports - &copy 2021-{{ Carbon\Carbon::now()->year }}
        </div>
    </div>
@endsection
