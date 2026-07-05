package com.photonbounce.leads.repository

import com.photonbounce.leads.api.LeadsApiService
import com.photonbounce.leads.models.DashboardStats
import com.photonbounce.leads.models.AnalyticsData
import com.photonbounce.leads.models.TierInfo
import com.photonbounce.leads.models.User

class UserRepository(private val api: LeadsApiService) {

    suspend fun getMe(): Result<User> {
        return try {
            val response = api.getMe()
            if (response.isSuccessful) {
                Result.success(response.body() ?: throw Exception("Empty response"))
            } else {
                Result.failure(Exception("Failed: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun updateProfile(name: String, timezone: String): Result<User> {
        return try {
            val response = api.updateMe(com.photonbounce.leads.models.UpdateProfileRequest(name, timezone))
            if (response.isSuccessful) {
                Result.success(response.body() ?: throw Exception("Empty response"))
            } else {
                Result.failure(Exception("Failed: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getDashboardStats(): Result<DashboardStats> {
        return try {
            val response = api.getDashboardStats()
            if (response.isSuccessful) {
                Result.success(response.body() ?: DashboardStats())
            } else {
                Result.failure(Exception("Failed: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getAnalytics(): Result<AnalyticsData> {
        return try {
            val response = api.getAnalytics()
            if (response.isSuccessful) {
                Result.success(response.body() ?: AnalyticsData())
            } else {
                Result.failure(Exception("Failed: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun getTier(): Result<TierInfo> {
        return try {
            val response = api.getTier()
            if (response.isSuccessful) {
                Result.success(response.body() ?: TierInfo())
            } else {
                Result.failure(Exception("Failed: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
