package com.photonbounce.leads.models

import com.google.gson.annotations.SerializedName

data class User(
    val id: String,
    val name: String,
    val email: String,
    val timezone: String = "UTC",
    val tier: String = "free",
    @SerializedName("created_at") val createdAt: String? = null
)

data class LoginRequest(
    val email: String,
    val password: String
)

data class RegisterRequest(
    val name: String,
    val email: String,
    val password: String
)

data class AuthResponse(
    val token: String,
    val user: User
)

data class UpdateProfileRequest(
    val name: String,
    val timezone: String
)
