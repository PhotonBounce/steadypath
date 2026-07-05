package com.photonbounce.leads.viewmodels

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.photonbounce.leads.db.LeadEntity
import com.photonbounce.leads.models.DashboardStats
import com.photonbounce.leads.repository.LeadsRepository
import com.photonbounce.leads.repository.UserRepository
import com.photonbounce.leads.utils.TokenManager
import kotlinx.coroutines.flow.catch
import kotlinx.coroutines.launch

class DashboardViewModel(
    private val userRepository: UserRepository,
    private val leadsRepository: LeadsRepository,
    private val tokenManager: TokenManager
) : ViewModel() {

    private val _stats = MutableLiveData<DashboardStats>()
    val stats: LiveData<DashboardStats> = _stats

    private val _recentLeads = MutableLiveData<List<LeadEntity>>()
    val recentLeads: LiveData<List<LeadEntity>> = _recentLeads

    private val _isRefreshing = MutableLiveData(false)
    val isRefreshing: LiveData<Boolean> = _isRefreshing

    private val _error = MutableLiveData<String?>()
    val error: LiveData<String?> = _error

    val isVip: Boolean get() = tokenManager.getUserTier() == "vip"

    init {
        viewModelScope.launch {
            leadsRepository.allLeads
                .catch { _error.value = it.message }
                .collect { leads ->
                    _recentLeads.value = leads.take(5)
                }
        }
    }

    fun refresh() {
        viewModelScope.launch {
            _isRefreshing.value = true

            val statsResult = userRepository.getDashboardStats()
            statsResult.onSuccess {
                _stats.value = it
            }.onFailure {
                _error.value = it.message
            }

            leadsRepository.refreshLeads()
            _isRefreshing.value = false
        }
    }
}
