package com.photonbounce.leads.repository

import com.photonbounce.leads.api.LeadsApiService
import com.photonbounce.leads.models.AuthResponse
import com.photonbounce.leads.models.LoginRequest
import com.photonbounce.leads.models.RegisterRequest
import com.photonbounce.leads.utils.TokenManager

class AuthRepository(
    private val api: LeadsApiService,
    private val tokenManager: TokenManager
) {

    suspend fun login(email: String, password: String): Result<AuthResponse> {
        return try {
            val response = api.login(LoginRequest(email, password))
            if (response.isSuccessful) {
                val auth = response.body()
                auth?.let {
                    tokenManager.saveToken(it.token)
                    tokenManager.saveUserInfo(
                        it.user.id,
                        it.user.name,
                        it.user.email,
                        it.user.tier
                    )
                }
                Result.success(auth ?: throw Exception("Empty response"))
            } else {
                Result.failure(Exception("Login failed: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun register(name: String, email: String, password: String): Result<AuthResponse> {
        return try {
            val response = api.register(RegisterRequest(name, email, password))
            if (response.isSuccessful) {
                val auth = response.body()
                auth?.let {
                    tokenManager.saveToken(it.token)
                    tokenManager.saveUserInfo(
                        it.user.id,
                        it.user.name,
                        it.user.email,
                        it.user.tier
                    )
                }
                Result.success(auth ?: throw Exception("Empty response"))
            } else {
                Result.failure(Exception("Registration failed: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    fun logout() {
        tokenManager.clearAll()
    }

    fun isLoggedIn(): Boolean = tokenManager.isLoggedIn()
}
