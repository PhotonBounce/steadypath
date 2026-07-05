package com.photonbounce.leads.viewmodels

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.photonbounce.leads.models.AnalyticsData
import com.photonbounce.leads.repository.UserRepository
import com.photonbounce.leads.utils.TokenManager
import kotlinx.coroutines.launch

class AnalyticsViewModel(
    private val userRepository: UserRepository,
    private val tokenManager: TokenManager
) : ViewModel() {

    private val _analytics = MutableLiveData<AnalyticsData>()
    val analytics: LiveData<AnalyticsData> = _analytics

    private val _isLoading = MutableLiveData(false)
    val isLoading: LiveData<Boolean> = _isLoading

    private val _error = MutableLiveData<String?>()
    val error: LiveData<String?> = _error

    val isVip: Boolean get() = tokenManager.getUserTier() == "vip"

    fun loadAnalytics() {
        if (!isVip) return
        viewModelScope.launch {
            _isLoading.value = true
            userRepository.getAnalytics()
                .onSuccess { _analytics.value = it }
                .onFailure { _error.value = it.message }
            _isLoading.value = false
        }
    }
}
