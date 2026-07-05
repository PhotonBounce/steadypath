package com.photonbounce.leads.viewmodels

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.photonbounce.leads.db.LeadEntity
import com.photonbounce.leads.repository.LeadsRepository
import kotlinx.coroutines.flow.catch
import kotlinx.coroutines.launch

class LeadsViewModel(private val repository: LeadsRepository) : ViewModel() {

    private val _leads = MutableLiveData<List<LeadEntity>>()
    val leads: LiveData<List<LeadEntity>> = _leads

    private val _isRefreshing = MutableLiveData(false)
    val isRefreshing: LiveData<Boolean> = _isRefreshing

    private val _error = MutableLiveData<String?>()
    val error: LiveData<String?> = _error

    private var currentStatus: String? = null
    private var currentSearch: String? = null

    init {
        viewModelScope.launch {
            repository.allLeads
                .catch { _error.value = it.message }
                .collect { _leads.value = it }
        }
    }

    fun refresh() {
        viewModelScope.launch {
            _isRefreshing.value = true
            repository.refreshLeads(currentStatus, currentSearch)
                .onFailure { _error.value = it.message }
            _isRefreshing.value = false
        }
    }

    fun filterByStatus(status: String?) {
        currentStatus = status
        viewModelScope.launch {
            repository.refreshLeads(status, currentSearch)
        }
    }

    fun search(query: String) {
        currentSearch = query.takeIf { it.isNotBlank() }
        viewModelScope.launch {
            if (query.isBlank()) {
                repository.allLeads.collect { _leads.value = it }
            } else {
                repository.searchLeads(query).collect { _leads.value = it }
            }
        }
    }

    fun updateLeadStatus(id: String, status: String) {
        viewModelScope.launch {
            repository.updateLead(id, status = status)
                .onFailure { _error.value = it.message }
        }
    }
}
