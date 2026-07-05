package com.photonbounce.leads.api

import com.photonbounce.leads.models.*
import retrofit2.Response
import retrofit2.http.*

interface LeadsApiService {

    // Auth
    @POST("auth/login")
    suspend fun login(@Body request: LoginRequest): Response<AuthResponse>

    @POST("auth/register")
    suspend fun register(@Body request: RegisterRequest): Response<AuthResponse>

    // User
    @GET("me")
    suspend fun getMe(): Response<User>

    @PUT("me")
    suspend fun updateMe(@Body request: UpdateProfileRequest): Response<User>

    // Microsites
    @GET("microsites")
    suspend fun getMicrosites(): Response<List<Microsite>>

    @POST("microsites")
    suspend fun createMicrosite(@Body request: CreateMicrositeRequest): Response<Microsite>

    @PUT("microsites/{id}")
    suspend fun updateMicrosite(
        @Path("id") id: String,
        @Body request: UpdateMicrositeRequest
    ): Response<Microsite>

    // Leads
    @GET("leads")
    suspend fun getLeads(
        @Query("status") status: String? = null,
        @Query("search") search: String? = null
    ): Response<List<Lead>>

    @PUT("leads/{id}")
    suspend fun updateLead(
        @Path("id") id: String,
        @Body request: UpdateLeadRequest
    ): Response<Lead>

    // Analytics
    @GET("analytics/dashboard")
    suspend fun getDashboardStats(): Response<DashboardStats>

    @GET("analytics")
    suspend fun getAnalytics(): Response<AnalyticsData>

    // Tier
    @GET("tier")
    suspend fun getTier(): Response<TierInfo>
}
