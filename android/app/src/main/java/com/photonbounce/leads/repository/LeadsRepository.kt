package com.photonbounce.leads.repository

import com.photonbounce.leads.api.LeadsApiService
import com.photonbounce.leads.db.*
import com.photonbounce.leads.models.*
import com.photonbounce.leads.utils.NetworkMonitor
import com.photonbounce.leads.utils.TokenManager
import kotlinx.coroutines.flow.*

class LeadsRepository(
    private val api: LeadsApiService,
    private val leadDao: LeadDao,
    private val micrositeDao: MicrositeDao,
    private val networkMonitor: NetworkMonitor,
    private val tokenManager: TokenManager
) {

    val isOnline = networkMonitor.isOnline

    val allLeads: Flow<List<LeadEntity>> = leadDao.getAllLeads()

    fun getLeadsByStatus(status: String): Flow<List<LeadEntity>> =
        leadDao.getLeadsByStatus(status)

    fun searchLeads(query: String): Flow<List<LeadEntity>> =
        if (query.isBlank()) leadDao.getAllLeads()
        else leadDao.searchLeads(query)

    suspend fun getLead(id: String): LeadEntity? = leadDao.getLeadById(id)

    suspend fun refreshLeads(status: String? = null, search: String? = null): Result<List<Lead>> {
        return try {
            val response = api.getLeads(status, search)
            if (response.isSuccessful) {
                val leads = response.body() ?: emptyList()
                leadDao.insertAll(leads.map { it.toEntity() })
                Result.success(leads)
            } else {
                Result.failure(Exception("Failed to fetch leads: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun updateLead(id: String, status: String? = null, notes: String? = null): Result<Lead> {
        val current = leadDao.getLeadById(id)
        current?.let {
            val updated = it.copy(
                pendingStatus = status ?: it.pendingStatus,
                pendingNotes = notes ?: it.pendingNotes,
                isSynced = false
            )
            leadDao.update(updated)
        }

        return try {
            val response = api.updateLead(id, UpdateLeadRequest(status, notes))
            if (response.isSuccessful) {
                val lead = response.body()
                lead?.let { leadDao.insert(it.toEntity().copy(isSynced = true)) }
                Result.success(lead ?: throw Exception("Empty response"))
            } else {
                Result.failure(Exception("Update failed: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun syncPendingChanges() {
        val unsynced = leadDao.getUnsyncedLeads()
        for (lead in unsynced) {
            try {
                val response = api.updateLead(
                    lead.id,
                    UpdateLeadRequest(lead.pendingStatus, lead.pendingNotes)
                )
                if (response.isSuccessful) {
                    leadDao.markSynced(lead.id)
                }
            } catch (_: Exception) {
                // Will retry later
            }
        }
    }

    // Extension to convert models
    private fun Lead.toEntity() = LeadEntity(
        id = id,
        name = name,
        email = email,
        phone = phone,
        company = company,
        status = status,
        source = source,
        micrositeId = micrositeId,
        mlScore = mlScore,
        niche = niche,
        notes = notes,
        createdAt = createdAt,
        updatedAt = updatedAt
    )
}
