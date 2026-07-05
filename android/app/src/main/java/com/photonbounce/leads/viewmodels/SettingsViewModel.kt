package com.photonbounce.leads.viewmodels

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.photonbounce.leads.models.TierInfo
import com.photonbounce.leads.models.User
import com.photonbounce.leads.repository.AuthRepository
import com.photonbounce.leads.repository.UserRepository
import com.photonbounce.leads.utils.TokenManager
import kotlinx.coroutines.launch

class SettingsViewModel(
    private val userRepository: UserRepository,
    private val authRepository: AuthRepository,
    private val tokenManager: TokenManager
) : ViewModel() {

    private val _user = MutableLiveData<User>()
    val user: LiveData<User> = _user

    private val _tier = MutableLiveData<TierInfo>()
    val tier: LiveData<TierInfo> = _tier

    private val _updateResult = MutableLiveData<Boolean>()
    val updateResult: LiveData<Boolean> = _updateResult

    private val _isLoading = MutableLiveData(false)
    val isLoading: LiveData<Boolean> = _isLoading

    private val _notificationsEnabled = MutableLiveData(true)
    val notificationsEnabled: LiveData<Boolean> = _notificationsEnabled

    val isVip: Boolean get() = tokenManager.getUserTier() == "vip"

    fun loadUserData() {
        viewModelScope.launch {
            _isLoading.value = true
            userRepository.getMe()
                .onSuccess {
                    _user.value = it
                    tokenManager.saveUserInfo(it.id, it.name, it.email, it.tier)
                }
            userRepository.getTier()
                .onSuccess { _tier.value = it }
            _isLoading.value = false
        }
    }

    fun updateProfile(name: String, timezone: String) {
        viewModelScope.launch {
            _isLoading.value = true
            userRepository.updateProfile(name, timezone)
                .onSuccess {
                    _user.value = it
                    _updateResult.value = true
                }
                .onFailure { _updateResult.value = false }
            _isLoading.value = false
        }
    }

    fun logout() {
        authRepository.logout()
    }

    fun setNotificationsEnabled(enabled: Boolean) {
        _notificationsEnabled.value = enabled
    }
}
