package com.photonbounce.leads.repository

import com.photonbounce.leads.api.LeadsApiService
import com.photonbounce.leads.db.MicrositeDao
import com.photonbounce.leads.db.MicrositeEntity
import com.photonbounce.leads.models.CreateMicrositeRequest
import com.photonbounce.leads.models.Microsite
import com.photonbounce.leads.models.UpdateMicrositeRequest
import kotlinx.coroutines.flow.Flow

class MicrositeRepository(
    private val api: LeadsApiService,
    private val micrositeDao: MicrositeDao
) {

    val allMicrosites: Flow<List<MicrositeEntity>> = micrositeDao.getAllMicrosites()

    suspend fun refreshMicrosites(): Result<List<Microsite>> {
        return try {
            val response = api.getMicrosites()
            if (response.isSuccessful) {
                val microsites = response.body() ?: emptyList()
                micrositeDao.insertAll(microsites.map { it.toEntity() })
                Result.success(microsites)
            } else {
                Result.failure(Exception("Failed to fetch microsites: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun createMicrosite(name: String, slug: String, niche: String?): Result<Microsite> {
        return try {
            val response = api.createMicrosite(CreateMicrositeRequest(name, slug, niche))
            if (response.isSuccessful) {
                val microsite = response.body()
                microsite?.let { micrositeDao.insert(it.toEntity()) }
                Result.success(microsite ?: throw Exception("Empty response"))
            } else {
                Result.failure(Exception("Failed to create: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun updateMicrosite(id: String, active: Boolean): Result<Microsite> {
        return try {
            val response = api.updateMicrosite(id, UpdateMicrositeRequest(active = active))
            if (response.isSuccessful) {
                val microsite = response.body()
                microsite?.let { micrositeDao.insert(it.toEntity()) }
                Result.success(microsite ?: throw Exception("Empty response"))
            } else {
                Result.failure(Exception("Failed to update: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getCount(): Int = micrositeDao.getCount()

    private fun Microsite.toEntity() = MicrositeEntity(
        id = id,
        name = name,
        slug = slug,
        niche = niche,
        active = active,
        leadCount = leadCount,
        createdAt = createdAt
    )
}
