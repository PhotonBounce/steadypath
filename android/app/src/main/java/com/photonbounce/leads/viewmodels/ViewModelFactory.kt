package com.photonbounce.leads.viewmodels

import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import com.photonbounce.leads.api.LeadsApiService
import com.photonbounce.leads.db.AppDatabase
import com.photonbounce.leads.repository.*
import com.photonbounce.leads.utils.NetworkMonitor
import com.photonbounce.leads.utils.TokenManager

@Suppress("UNCHECKED_CAST")
class ViewModelFactory(
    private val api: LeadsApiService,
    private val database: AppDatabase,
    private val tokenManager: TokenManager,
    private val networkMonitor: NetworkMonitor
) : ViewModelProvider.Factory {

    private val authRepository by lazy { AuthRepository(api, tokenManager) }
    private val userRepository by lazy { UserRepository(api) }
    private val leadsRepository by lazy {
        LeadsRepository(api, database.leadDao(), database.micrositeDao(), networkMonitor, tokenManager)
    }
    private val micrositeRepository by lazy {
        MicrositeRepository(api, database.micrositeDao())
    }

    override fun <T : ViewModel> create(modelClass: Class<T>): T {
        return when {
            modelClass.isAssignableFrom(AuthViewModel::class.java) ->
                AuthViewModel(authRepository) as T
            modelClass.isAssignableFrom(DashboardViewModel::class.java) ->
                DashboardViewModel(userRepository, leadsRepository, tokenManager) as T
            modelClass.isAssignableFrom(LeadsViewModel::class.java) ->
                LeadsViewModel(leadsRepository) as T
            modelClass.isAssignableFrom(MicrositesViewModel::class.java) ->
                MicrositesViewModel(micrositeRepository, tokenManager) as T
            modelClass.isAssignableFrom(AnalyticsViewModel::class.java) ->
                AnalyticsViewModel(userRepository, tokenManager) as T
            modelClass.isAssignableFrom(SettingsViewModel::class.java) ->
                SettingsViewModel(userRepository, authRepository, tokenManager) as T
            else -> throw IllegalArgumentException("Unknown ViewModel class: ${modelClass.name}")
        }
    }
}
