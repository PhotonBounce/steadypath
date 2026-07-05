package com.photonbounce.leads.viewmodels

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.photonbounce.leads.db.MicrositeEntity
import com.photonbounce.leads.repository.MicrositeRepository
import com.photonbounce.leads.utils.TokenManager
import kotlinx.coroutines.flow.catch
import kotlinx.coroutines.launch

class MicrositesViewModel(
    private val repository: MicrositeRepository,
    private val tokenManager: TokenManager
) : ViewModel() {

    private val _microsites = MutableLiveData<List<MicrositeEntity>>()
    val microsites: LiveData<List<MicrositeEntity>> = _microsites

    private val _isRefreshing = MutableLiveData(false)
    val isRefreshing: LiveData<Boolean> = _isRefreshing

    private val _error = MutableLiveData<String?>()
    val error: LiveData<String?> = _error

    private val _createResult = MutableLiveData<Boolean>()
    val createResult: LiveData<Boolean> = _createResult

    val isVip: Boolean get() = tokenManager.getUserTier() == "vip"
    val maxMicrosites: Int get() = if (isVip) Int.MAX_VALUE else 1

    init {
        viewModelScope.launch {
            repository.allMicrosites
                .catch { _error.value = it.message }
                .collect { _microsites.value = it }
        }
    }

    fun refresh() {
        viewModelScope.launch {
            _isRefreshing.value = true
            repository.refreshMicrosites()
                .onFailure { _error.value = it.message }
            _isRefreshing.value = false
        }
    }

    fun createMicrosite(name: String, slug: String, niche: String?) {
        viewModelScope.launch {
            if (!isVip && repository.getCount() >= 1) {
                _error.value = "Free tier limited to 1 microsite. Upgrade to VIP!"
                return@launch
            }
            val result = repository.createMicrosite(name, slug, niche)
            result.onSuccess {
                _createResult.value = true
            }.onFailure {
                _error.value = it.message
                _createResult.value = false
            }
        }
    }

    fun toggleMicrosite(id: String, active: Boolean) {
        viewModelScope.launch {
            repository.updateMicrosite(id, active)
                .onFailure { _error.value = it.message }
        }
    }
}
