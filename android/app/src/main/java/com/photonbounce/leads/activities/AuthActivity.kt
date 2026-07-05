package com.photonbounce.leads.activities

import android.content.Intent
import android.os.Bundle
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.ViewModelProvider
import com.photonbounce.leads.LeadsApplication
import com.photonbounce.leads.databinding.ActivityAuthBinding
import com.photonbounce.leads.viewmodels.AuthViewModel
import com.photonbounce.leads.viewmodels.ViewModelFactory

class AuthActivity : AppCompatActivity() {

    private lateinit var binding: ActivityAuthBinding
    private lateinit var viewModel: AuthViewModel

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityAuthBinding.inflate(layoutInflater)
        setContentView(binding.root)

        val app = application as LeadsApplication
        val factory = ViewModelFactory(app.api, app.database, app.tokenManager, app.networkMonitor)
        viewModel = ViewModelProvider(this, factory)[AuthViewModel::class.java]

        viewModel.authState.observe(this) { state ->
            when (state) {
                is AuthViewModel.AuthState.Success,
                is AuthViewModel.AuthState.Authenticated -> {
                    startActivity(Intent(this, MainActivity::class.java))
                    finish()
                }
                else -> { /* handled in fragments */ }
            }
        }
    }
}
