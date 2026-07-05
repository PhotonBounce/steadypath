package com.photonbounce.leads.fragments

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import androidx.fragment.app.Fragment
import androidx.lifecycle.ViewModelProvider
import com.photonbounce.leads.LeadsApplication
import com.photonbounce.leads.databinding.FragmentRegisterBinding
import com.photonbounce.leads.viewmodels.AuthViewModel
import com.photonbounce.leads.viewmodels.ViewModelFactory

class RegisterFragment : Fragment() {

    private var _binding: FragmentRegisterBinding? = null
    private val binding get() = _binding!!
    private lateinit var viewModel: AuthViewModel

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentRegisterBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        val app = requireActivity().application as LeadsApplication
        val factory = ViewModelFactory(app.api, app.database, app.tokenManager, app.networkMonitor)
        viewModel = ViewModelProvider(requireActivity(), factory)[AuthViewModel::class.java]

        binding.btnRegister.setOnClickListener {
            val name = binding.etName.text.toString().trim()
            val email = binding.etEmail.text.toString().trim()
            val password = binding.etPassword.text.toString().trim()

            var valid = true
            if (name.isEmpty()) { binding.etName.error = "Required"; valid = false }
            if (email.isEmpty()) { binding.etEmail.error = "Required"; valid = false }
            if (password.length < 6) { binding.etPassword.error = "Min 6 characters"; valid = false }

            if (valid) viewModel.register(name, email, password)
        }

        binding.tvLogin.setOnClickListener {
            parentFragmentManager.popBackStack()
        }

        viewModel.isLoading.observe(viewLifecycleOwner) {
            binding.progressBar.visibility = if (it) View.VISIBLE else View.GONE
            binding.btnRegister.isEnabled = !it
        }

        viewModel.authState.observe(viewLifecycleOwner) { state ->
            when (state) {
                is AuthViewModel.AuthState.Error -> {
                    binding.tvError.text = state.message
                    binding.tvError.visibility = View.VISIBLE
                }
                else -> binding.tvError.visibility = View.GONE
            }
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
